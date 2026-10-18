<?php
declare(strict_types=1);

const LOGIN_MAX_FAILS = 5;
const LOGIN_LOCK_SECONDS = 900;
const LOGIN_GLOBAL_MAX = 50;
const LOGIN_MAX_ENTRIES = 500;
const SESSION_LIFETIME = 30 * 24 * 3600;
const SESSION_ANON_LIFETIME = 2 * 3600;
const TRUST_COOKIE = 'alfa_trusted';   // „dôveryhodné zariadenie“ — obíde globálnu blokáciu prihlasovania
const LOGIN_CSRF_COOKIE = 'alfa_login'; // CSRF prihlasovacieho formulára bez relácie
const PASSWORD_MIN = 8;
const PASSWORD_MAX_BYTES = 72; // bcrypt ďalšie bajty ignoruje
const USER_MAX = 254; // najdlhší platný e-mail
const AUTH_HASH_OPTIONS = ['cost' => 12];
// hash náhodného hesla — overuje sa proti nemu pri zlom mene, aby odpoveď trvala rovnako dlho
const AUTH_DUMMY_HASH = '$2y$12$N2wIW8258KFL3mWnppaAJOwjVxCCMG3hXs/SzhqYMtba2H.iCKVzq';

/* ---------- e-mail a heslo v data/auth.json (nie v PHP súbore — opcache, rovnaká ochrana ako dáta) ---------- */

function auth_file(): string
{
    return data_dir() . '/auth.json';
}

/** Obsah auth.json; [] ak neexistuje alebo je poškodený. */
function auth_data(): array
{
    $file = auth_file();
    if (!is_file($file)) return [];
    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data)) {
        error_log('data/auth.json je poškodený');
        return [];
    }
    return $data;
}

/** Hash hesla; '' ak heslo nie je nastavené alebo je auth.json poškodený (vtedy sa nikto neprihlási). */
function auth_hash(): string
{
    $hash = auth_data()['hash'] ?? '';
    return is_string($hash) ? $hash : '';
}

/** Prihlasovací e-mail; '' ak chýba (starý auth.json bez e-mailu → nikto sa neprihlási). */
function auth_user(): string
{
    $user = auth_data()['user'] ?? '';
    return is_string($user) && auth_user_error($user) === null ? $user : '';
}

/** Zapíše e-mail + hash. $exclusive = true (install): len ak auth.json ešte neexistuje, inak vráti false. */
function auth_write_credentials(string $user, string $hash, bool $exclusive = false): bool
{
    ensure_storage();
    $json = json_encode(['user' => $user, 'hash' => $hash, 'changed' => now_iso()], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
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

function auth_hash_password(string $pw): string
{
    return password_hash($pw, PASSWORD_DEFAULT, AUTH_HASH_OPTIONS);
}

/** Odtlačok e-mailu + hesla; relácia ho drží a po zmene e-mailu alebo hesla prestane sedieť. */
function auth_pw_version(): string
{
    $hash = auth_hash();
    $user = auth_user();
    return $hash === '' || $user === '' ? '' : substr(hash('sha256', $user . "\n" . $hash), 0, 16);
}

/* ---------- pravidlá pre e-mail a heslo ---------- */

/** E-mail pri prihlásení nerozlišuje veľké/malé písmená. */
function auth_normalize_user(string $user): string
{
    return strtolower(trim($user));
}

/** null = e-mail je v poriadku, inak slovenská hláška. Očakáva už normalizovaný e-mail. */
function auth_user_error(string $user): ?string
{
    if (strlen($user) > USER_MAX || filter_var($user, FILTER_VALIDATE_EMAIL) === false) return 'Zadaj platný e-mail.';
    return null;
}

/** Časť e-mailu pred @ — heslo ju nesmie obsahovať (krátke časti do 2 znakov sa nekontrolujú). */
function auth_password_has_name(string $pw, string $user): bool
{
    $name = strstr($user, '@', true);
    $name = $name === false ? $user : $name;
    return str_len($name) >= 3 && str_contains(mb_strtolower($pw), $name);
}

/** null = heslo spĺňa pravidlá, inak slovenská hláška s tým, čo chýba. */
function auth_password_error(string $pw, string $user): ?string
{
    if (strlen($pw) > PASSWORD_MAX_BYTES) return 'Heslo je príliš dlhé (najviac ' . PASSWORD_MAX_BYTES . ' znakov bez diakritiky).';
    $missing = [];
    if (str_len($pw) < PASSWORD_MIN) $missing[] = 'aspoň ' . PASSWORD_MIN . ' znakov';
    if (preg_match('/\p{Lu}/u', $pw) !== 1) $missing[] = 'veľké písmeno';
    if (preg_match('/\p{Ll}/u', $pw) !== 1) $missing[] = 'malé písmeno';
    if (preg_match('/\p{Nd}/u', $pw) !== 1) $missing[] = 'číslicu';
    if (preg_match('/[^\p{L}\p{N}\s]/u', $pw) !== 1) $missing[] = 'špeciálny znak (napr. ! ? # * - _)';
    if ($missing) return 'Heslu chýba: ' . implode(', ', $missing) . '.';
    if (auth_password_has_name($pw, $user)) return 'Heslo nesmie obsahovať časť e-mailu pred @.';
    return null;
}

/* ---------- relácia ---------- */

/**
 * Relácie v data/sessions cez vlastný handler. Hosting (napr. Webglobe) vie session.save_path vnútiť
 * cez php_admin_value — session_save_path() sa potom ticho ignoruje a vnútený priečinok nemusí byť
 * zapisovateľný. Vlastný handler od toho nezávisí a nemaže relácie podľa kratšieho limitu hostingu.
 */
final class AlfaSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    /** @var resource|null */
    private $fp = null;
    private string $id = '';

    public function __construct(private string $dir) {}

    private function file(string $id): ?string
    {
        return preg_match('/^[a-zA-Z0-9,-]{22,256}$/', $id) ? $this->dir . '/sess_' . $id : null;
    }

    /** Otvorí a zamkne súbor relácie (zámok drží do close, ako vstavaný handler „files“). */
    private function lock(string $id): bool
    {
        if ($this->fp !== null && $this->id === $id) return true;
        $this->release();
        $file = $this->file($id);
        if ($file === null) return false;
        $fp = @fopen($file, 'c+');
        if ($fp === false) return false;
        flock($fp, LOCK_EX);
        $this->fp = $fp;
        $this->id = $id;
        return true;
    }

    private function release(): void
    {
        if ($this->fp === null) return;
        flock($this->fp, LOCK_UN);
        fclose($this->fp);
        $this->fp = null;
        $this->id = '';
    }

    public function open(string $path, string $name): bool { return true; }

    public function close(): bool
    {
        $this->release();
        return true;
    }

    /** Neexistujúca relácia = prázdna; súbor sa nevytvára (anonymné návštevy adminu nezakladajú súbory). */
    public function read(string $id): string|false
    {
        $file = $this->file($id);
        if ($file === null) return false;
        if (!is_file($file)) return '';
        if (!$this->lock($id)) return false;
        rewind($this->fp);
        return (string)stream_get_contents($this->fp);
    }

    public function write(string $id, string $data): bool
    {
        $file = $this->file($id);
        if ($file !== null && $data === '' && !is_file($file)) return true;
        if (!$this->lock($id)) return false;
        rewind($this->fp);
        ftruncate($this->fp, 0);
        return fwrite($this->fp, $data) === strlen($data) && fflush($this->fp);
    }

    public function destroy(string $id): bool
    {
        if ($this->id === $id) $this->release();
        $file = $this->file($id);
        if ($file !== null && is_file($file)) @unlink($file);
        return true;
    }

    /**
     * Limit hostingu (session.gc_maxlifetime) ignorujeme — prihlásená relácia platí SESSION_LIFETIME,
     * neprihlásená (napr. rozpracovaný formulár bez prihlásenia) len SESSION_ANON_LIFETIME.
     */
    public function gc(int $max_lifetime): int|false
    {
        $n = 0;
        foreach (glob($this->dir . '/sess_*') ?: [] as $f) {
            $age = time() - (int)@filemtime($f);
            $old = $age > SESSION_LIFETIME
                || ($age > SESSION_ANON_LIFETIME && !str_contains((string)@file_get_contents($f, false, null, 0, 4096), 'admin|b:1;'));
            if ($old && @unlink($f)) $n++;
        }
        return $n;
    }

    public function validateId(string $id): bool
    {
        $file = $this->file($id);
        return $file !== null && is_file($file);
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        $file = $this->file($id);
        return $file !== null && @touch($file);
    }
}

function auth_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ensure_storage();
    session_set_save_handler(new AlfaSessionHandler(data_dir() . '/sessions'), true);
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

/** Je návštevník verejnej stránky prihlásený tréner? (koncepty) Reláciu otvorí len ak má cookie adminu. */
function admin_viewer(): bool
{
    if (!isset($_COOKIE['alfa_admin']) || !is_string($_COOKIE['alfa_admin'])) return false;
    auth_start_session();
    $ok = auth_is_logged_in();
    session_write_close();
    return $ok;
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

/**
 * Vráti 'ok' | 'bad' | 'locked'. Pokus sa započíta ešte pred overením hesla (súbežné požiadavky nedostanú navyše).
 * Zlý e-mail a zlé heslo vyzerajú rovnako (aj časovo) — útočník nezistí, či uhádol e-mail.
 */
function auth_attempt(string $user, string $password, string $clientKey, ?int $now = null, bool $trusted = false): string
{
    $now ??= time();
    $gate = auth_rate_register($clientKey, $now, $trusted); // 'locked' | 'check' | 'last'
    if ($gate === 'locked') return 'locked';
    $hash = auth_hash();
    $stored = auth_user();
    $userOk = $stored !== '' && hash_equals($stored, auth_normalize_user($user));
    $pwOk = strlen($password) <= 4096 && password_verify($password, $hash !== '' ? $hash : AUTH_DUMMY_HASH);
    if ($userOk && $pwOk && $hash !== '') {
        auth_rate_success($clientKey, $trusted);
        if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['pw'] = auth_pw_version();
        unset($_SESSION['csrf']); // nový CSRF token po prihlásení
        return 'ok';
    }
    return $gate === 'last' ? 'locked' : 'bad';
}

/** V jednom zamknutom kroku: skontroluje blokácie a započíta pokus. 'last' = týmto pokusom sa práve zablokovalo. */
function auth_rate_register(string $key, int $now, bool $trusted = false): string
{
    return store_update('login-attempts', 'attempts', function (array &$all) use ($key, $now, $trusted): string {
        $g = $all['*'] ?? ['times' => [], 'locked_until' => 0];
        $g['times'] = array_values(array_filter((array)($g['times'] ?? []), fn($t): bool => is_int($t) && $t > $now - LOGIN_LOCK_SECONDS));
        foreach ($all as $k => $a) {
            if ($k !== '*' && (($a['locked_until'] ?? 0) <= $now) && (($a['last'] ?? 0) < $now - LOGIN_LOCK_SECONDS)) unset($all[$k]);
        }
        $a = $all[$key] ?? ['fails' => 0, 'locked_until' => 0, 'last' => 0];
        if ((!$trusted && ($g['locked_until'] ?? 0) > $now) || $a['locked_until'] > $now) {
            $all['*'] = $g;
            return 'locked';
        }
        if ($a['last'] < $now - LOGIN_LOCK_SECONDS) $a['fails'] = 0;
        $a['fails']++;
        $a['last'] = $now;
        $a['locked_until'] = 0;
        if (!$trusted) $g['times'][] = $now;
        $last = false;
        if ($a['fails'] >= LOGIN_MAX_FAILS) {
            $a['fails'] = 0;
            $a['locked_until'] = $now + LOGIN_LOCK_SECONDS;
            $last = true;
        }
        if (!$trusted && count($g['times']) >= LOGIN_GLOBAL_MAX) {
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
function auth_rate_success(string $key, bool $trusted = false): void
{
    store_update('login-attempts', 'attempts', function (array &$all) use ($key, $trusted): void {
        unset($all[$key]);
        if (!$trusted && !empty($all['*']['times'])) array_pop($all['*']['times']);
    }, true);
}

/* ---------- dôveryhodné zariadenie a CSRF prihlásenia ---------- */

/** Podpis zariadenia, kde sa už tréner prihlásil; kľúč = hash hesla → zmena hesla/e-mailu ho zneplatní. */
function auth_trust_value(): string
{
    $hash = auth_hash();
    return $hash === '' ? '' : hash_hmac('sha256', 'trusted|' . auth_user(), $hash);
}

function auth_is_trusted(?array $cookies = null): bool
{
    $v = auth_trust_value();
    $c = ($cookies ?? $_COOKIE)[TRUST_COOKIE] ?? '';
    return $v !== '' && is_string($c) && hash_equals($v, $c);
}

function auth_cookie(string $name, string $value, int $lifetime): void
{
    if (headers_sent($file, $line)) {
        error_log("Cookie $name sa nedá nastaviť — výstup už začal ($file:$line)");
        return;
    }
    setcookie($name, $value, ['expires' => $value === '' ? time() - 3600 : time() + $lifetime, 'path' => '/',
        'secure' => request_is_https(), 'httponly' => true, 'samesite' => 'Strict']);
}

/** CSRF prihlasovacieho formulára cez cookie (double submit) — anonymný návštevník nepotrebuje reláciu. */
function login_csrf_token(): string
{
    static $token = null;
    if ($token !== null) return $token;
    $c = $_COOKIE[LOGIN_CSRF_COOKIE] ?? '';
    $token = is_string($c) && preg_match('/^[a-f0-9]{64}$/', $c) ? $c : bin2hex(random_bytes(32));
    if ($token !== $c) auth_cookie(LOGIN_CSRF_COOKIE, $token, 3600 * 12);
    return $token;
}

function login_csrf_check(mixed $posted, ?array $cookies = null): bool
{
    $c = ($cookies ?? $_COOKIE)[LOGIN_CSRF_COOKIE] ?? '';
    return is_string($posted) && is_string($c) && preg_match('/^[a-f0-9]{64}$/', $c) === 1 && hash_equals($c, $posted);
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

/* ---------- zmena e-mailu a hesla ---------- */

/** Overí e-mail + heslo podľa pravidiel a zapíše ich. Aktuálne zariadenie ostane prihlásené, ostatné sa odhlásia. */
function auth_set_credentials(string $user, string $new): void
{
    $user = auth_normalize_user($user);
    if ($err = auth_user_error($user)) throw new ValidationException(['user' => $err]);
    if ($err = auth_password_error($new, $user)) throw new ValidationException(['new' => $err]);
    auth_write_credentials($user, auth_hash_password($new));
    if (($_SESSION['admin'] ?? false) === true) $_SESSION['pw'] = auth_pw_version();
}

/** Zmena e-mailu a/alebo hesla. Prázdne nové heslo = heslo ostáva, mení sa len e-mail. */
function auth_change_credentials(string $old, string $user, string $new, string $new2): void
{
    $hash = auth_hash();
    if ($hash === '' || !password_verify($old, $hash)) throw new ValidationException(['old' => 'Súčasné heslo nesedí.']);
    $user = auth_normalize_user($user);
    if ($err = auth_user_error($user)) throw new ValidationException(['user' => $err]);
    if ($new === '' && $new2 === '') {
        if (auth_password_has_name($old, $user)) throw new ValidationException(['user' => 'Heslo nesmie obsahovať časť e-mailu pred @.']);
        auth_write_credentials($user, $hash);
        if (($_SESSION['admin'] ?? false) === true) $_SESSION['pw'] = auth_pw_version();
        return;
    }
    if ($err = auth_password_error($new, $user)) throw new ValidationException(['new' => $err]);
    if ($new !== $new2) throw new ValidationException(['new2' => 'Nové heslá sa nezhodujú.']);
    auth_set_credentials($user, $new);
}
