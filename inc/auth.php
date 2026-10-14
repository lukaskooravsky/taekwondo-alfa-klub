<?php
declare(strict_types=1);

const LOGIN_MAX_FAILS = 5;
const LOGIN_LOCK_SECONDS = 900;
const LOGIN_GLOBAL_MAX = 50;
const LOGIN_MAX_ENTRIES = 500;
const SESSION_LIFETIME = 30 * 24 * 3600;
const PASSWORD_MIN = 10;

/* ---------- heslo v data/auth.json (nie v PHP súbore — opcache, rovnaká ochrana ako dáta) ---------- */

function auth_file(): string
{
    return data_dir() . '/auth.json';
}

/** Hash hesla; '' ak heslo nie je nastavené alebo je auth.json poškodený (vtedy sa nikto neprihlási). */
function auth_hash(): string
{
    $file = auth_file();
    if (!is_file($file)) return '';
    $data = json_decode((string)file_get_contents($file), true);
    $hash = is_array($data) ? ($data['hash'] ?? '') : '';
    if (!is_string($hash) || $hash === '') {
        error_log('data/auth.json je poškodený');
        return '';
    }
    return $hash;
}

/** Zapíše hash. $exclusive = true (install): len ak auth.json ešte neexistuje, inak vráti false. */
function auth_write_hash(string $hash, bool $exclusive = false): bool
{
    ensure_storage();
    $json = json_encode(['hash' => $hash, 'changed' => now_iso()], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if ($exclusive) {
        $fh = @fopen(auth_file(), 'x');
        if ($fh === false) return false;
        $ok = fwrite($fh, $json) === strlen($json);
        fclose($fh);
        if (!$ok) {
            @unlink(auth_file());
            throw new RuntimeException('Nepodarilo sa uložiť heslo.');
        }
        return true;
    }
    $tmp = auth_file() . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $json) === false || !rename($tmp, auth_file())) {
        @unlink($tmp);
        throw new RuntimeException('Nepodarilo sa uložiť heslo.');
    }
    return true;
}

/** Odtlačok aktuálneho hesla; relácia ho drží a po zmene hesla prestane sedieť. */
function auth_pw_version(): string
{
    $hash = auth_hash();
    return $hash === '' ? '' : substr(hash('sha256', $hash), 0, 16);
}

/* ---------- relácia ---------- */

function auth_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ensure_storage();
    // vlastný priečinok relácií, aby hosting nemazal relácie skôr ako po 30 dňoch
    ini_set('session.save_handler', 'files'); // hosting môže mať predvolene iný handler (napr. redis)
    session_save_path(data_dir() . '/sessions');
    ini_set('session.gc_maxlifetime', (string)SESSION_LIFETIME);
    ini_set('session.use_strict_mode', '1');
    // vlastný priečinok cron hostingu nečistí → upratovanie starých relácií necháme na PHP (1 % požiadaviek)
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');
    session_name('alfa_admin');
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'secure' => request_is_https(), // rešpektuje aj X-Forwarded-Proto z proxy hostingu
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function auth_is_logged_in(): bool
{
    if (($_SESSION['admin'] ?? false) !== true) return false;
    $version = auth_pw_version();
    return $version !== '' && hash_equals($version, (string)($_SESSION['pw'] ?? ''));
}

function auth_require(): void
{
    auth_start_session();
    if (!auth_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/* ---------- klient a rate-limit ---------- */

/**
 * IP klienta. Websupport má pred Apache nginx proxy: vtedy je REMOTE_ADDR súkromná/loopback adresa
 * a skutočnú adresu nesie X-Real-IP / X-Forwarded-For. Pri verejnej REMOTE_ADDR sa hlavičky ignorujú
 * (klient by si ich vedel podvrhnúť).
 */
function auth_client_ip(?array $server = null): string
{
    $s = $server ?? $_SERVER;
    $remote = (string)($s['REMOTE_ADDR'] ?? '');
    if (filter_var($remote, FILTER_VALIDATE_IP) === false) return $remote === '' ? 'cli' : $remote;
    if (filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
        return $remote;
    }
    // nginx ($proxy_add_x_forwarded_for) pridáva skutočnú adresu NA KONIEC — začiatok zoznamu môže poslať útočník
    $forwarded = explode(',', (string)($s['HTTP_X_FORWARDED_FOR'] ?? ''));
    foreach ([(string)($s['HTTP_X_REAL_IP'] ?? ''), (string)end($forwarded)] as $candidate) {
        $candidate = trim($candidate);
        if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) return $candidate;
    }
    return $remote;
}

/** Kľúč klienta pre rate-limit: IPv4 celá adresa, IPv6 celá /64 sieť (jeden domov/zariadenie). */
function auth_client_key(?string $ip = null): string
{
    $ip ??= auth_client_ip();
    $bin = @inet_pton($ip);
    if ($bin !== false && strlen($bin) === 16) {
        $ip = str_starts_with($bin, str_repeat("\0", 10) . "\xff\xff")
            ? (string)inet_ntop(substr($bin, 12))           // ::ffff:a.b.c.d = IPv4, nie spoločná /64
            : bin2hex(substr($bin, 0, 8)) . '::/64';
    }
    return hash('sha256', $ip);
}

/** Vráti 'ok' | 'bad' | 'locked'. Pokus sa započíta ešte pred overením hesla (súbežné požiadavky nedostanú navyše). */
function auth_attempt(string $password, string $clientKey, ?int $now = null): string
{
    $now ??= time();
    $gate = auth_rate_register($clientKey, $now); // 'locked' | 'check' | 'last'
    if ($gate === 'locked') return 'locked';
    $hash = auth_hash();
    if ($hash !== '' && password_verify($password, $hash)) {
        auth_rate_success($clientKey);
        if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['pw'] = auth_pw_version();
        unset($_SESSION['csrf']); // nový CSRF token po prihlásení
        return 'ok';
    }
    return $gate === 'last' ? 'locked' : 'bad';
}

/** V jednom zamknutom kroku: skontroluje blokácie a započíta pokus. 'last' = týmto pokusom sa práve zablokovalo. */
function auth_rate_register(string $key, int $now): string
{
    return store_update('login-attempts', 'attempts', function (array &$all) use ($key, $now): string {
        $g = $all['*'] ?? ['times' => [], 'locked_until' => 0];
        $g['times'] = array_values(array_filter((array)($g['times'] ?? []), fn($t): bool => is_int($t) && $t > $now - LOGIN_LOCK_SECONDS));
        foreach ($all as $k => $a) {
            if ($k !== '*' && (($a['locked_until'] ?? 0) <= $now) && (($a['last'] ?? 0) < $now - LOGIN_LOCK_SECONDS)) unset($all[$k]);
        }
        $a = $all[$key] ?? ['fails' => 0, 'locked_until' => 0, 'last' => 0];
        if (($g['locked_until'] ?? 0) > $now || $a['locked_until'] > $now) {
            $all['*'] = $g;
            return 'locked';
        }
        if ($a['last'] < $now - LOGIN_LOCK_SECONDS) $a['fails'] = 0;
        $a['fails']++;
        $a['last'] = $now;
        $a['locked_until'] = 0;
        $g['times'][] = $now;
        $last = false;
        if ($a['fails'] >= LOGIN_MAX_FAILS) {
            $a['fails'] = 0;
            $a['locked_until'] = $now + LOGIN_LOCK_SECONDS;
            $last = true;
        }
        if (count($g['times']) >= LOGIN_GLOBAL_MAX) {
            $g['times'] = [];
            $g['locked_until'] = $now + LOGIN_LOCK_SECONDS;
            $last = true;
        }
        $all[$key] = $a;
        $all['*'] = $g;
        auth_rate_trim($all);
        return $last ? 'last' : 'check';
    }, true);
}

/** Úspešné prihlásenie: vynuluje klienta a jeho pokus sa nepočíta do globálneho stropu. */
function auth_rate_success(string $key): void
{
    store_update('login-attempts', 'attempts', function (array &$all) use ($key): void {
        unset($all[$key]);
        if (!empty($all['*']['times'])) array_pop($all['*']['times']);
    }, true);
}

/** Najviac LOGIN_MAX_ENTRIES klientov (ponechá najnovších) + globálny záznam '*'. */
function auth_rate_trim(array &$all): void
{
    $global = $all['*'] ?? null;
    unset($all['*']);
    if (count($all) > LOGIN_MAX_ENTRIES) {
        uasort($all, fn(array $a, array $b): int => ($b['last'] ?? 0) <=> ($a['last'] ?? 0));
        $all = array_slice($all, 0, LOGIN_MAX_ENTRIES, true);
    }
    if ($global !== null) $all['*'] = $global;
}

function auth_logout(): void
{
    auth_start_session();
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    session_destroy();
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_check(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/* ---------- zmena hesla ---------- */

function auth_set_password(string $new): void
{
    if (str_len($new) < PASSWORD_MIN) throw new ValidationException(['new' => 'Heslo musí mať aspoň 10 znakov.']);
    auth_write_hash(password_hash($new, PASSWORD_DEFAULT));
    // aktuálne zariadenie ostane prihlásené, ostatné relácie majú starú verziu hesla
    if (($_SESSION['admin'] ?? false) === true) $_SESSION['pw'] = auth_pw_version();
}

function auth_change_password(string $old, string $new, string $new2): void
{
    if (!password_verify($old, auth_hash())) throw new ValidationException(['old' => 'Súčasné heslo nesedí.']);
    if (str_len($new) < PASSWORD_MIN) throw new ValidationException(['new' => 'Heslo musí mať aspoň 10 znakov.']);
    if ($new !== $new2) throw new ValidationException(['new2' => 'Nové heslá sa nezhodujú.']);
    auth_set_password($new);
}
