<?php
declare(strict_types=1);

/** Skopíruje ukážkové dáta a obrázky. Existujúce súbory nikdy neprepíše. */
function install_seed(string $seedDir): void
{
    ensure_storage();
    foreach (glob("$seedDir/data/*.json") ?: [] as $src) {
        $dest = data_dir() . '/' . basename($src);
        if (!is_file($dest)) copy($src, $dest);
    }
    $base = "$seedDir/uploads";
    if (!is_dir($base)) return;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($it as $item) {
        $rel = str_replace('\\', '/', substr($item->getPathname(), strlen($base) + 1));
        $dest = uploads_dir() . '/' . $rel;
        if ($item->isDir()) {
            if (!is_dir($dest)) mkdir($dest, 0775, true);
        } elseif (!is_file($dest)) {
            copy($item->getPathname(), $dest);
        }
    }
}

/** Vytvorí súbor s jednorazovým tokenom. Je to PHP, ktoré hneď skončí — pri priamom otvorení nič neprezradí. */
function install_token_write(string $file): string
{
    $token = bin2hex(random_bytes(32));
    $php = "<?php exit; // Jednorazový token pre install.php. Po inštalácii sa zmaže sám.\n// TOKEN=$token\n";
    if (file_put_contents($file, $php) === false) throw new RuntimeException("Nepodarilo sa zapísať $file");
    return $token;
}

/** Token sa číta ako text (nie require), aby sa nikdy nevykonal cudzí kód. */
function install_token_read(string $file): ?string
{
    if (!is_file($file)) return null;
    return preg_match('/^\/\/ TOKEN=([a-f0-9]{64})$/mD', (string)file_get_contents($file), $m) === 1 ? $m[1] : null;
}

function install_token_valid(?string $given, string $file): bool
{
    $token = install_token_read($file);
    return $token !== null && is_string($given) && $given !== '' && hash_equals($token, $given);
}

/** Čo hostingu chýba (po slovensky). 'required' blokuje inštaláciu, 'recommended' len upozorní. */
function install_requirements(): array
{
    $req = [];
    if (PHP_VERSION_ID < 80100) $req[] = 'PHP 8.1 alebo novšie (teraz ' . PHP_VERSION . '; na Websupporte nastav vo WebAdmine 8.3+)';
    foreach (['fileinfo', 'mbstring', 'json'] as $ext) {
        if (!extension_loaded($ext)) $req[] = "rozšírenie PHP „$ext“";
    }
    $rec = [];
    if (!extension_loaded('gd')) $rec[] = 'rozšírenie PHP „gd“ (bez neho server nezmenší fotky, ak to nespraví prehliadač)';
    return ['required' => $req, 'recommended' => $rec];
}

/** Nastaví prvé heslo. false = heslo už existuje (inštalácia prebehla, alebo súbežný pokus). */
function install_set_password(string $pw, string $pw2): bool
{
    if (str_len($pw) < PASSWORD_MIN) throw new ValidationException(['password' => 'Heslo musí mať aspoň 10 znakov.']);
    if ($pw !== $pw2) throw new ValidationException(['password2' => 'Heslá sa nezhodujú.']);
    return auth_write_hash(password_hash($pw, PASSWORD_DEFAULT), true);
}
