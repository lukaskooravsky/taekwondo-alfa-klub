<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
date_default_timezone_set('Europe/Bratislava');
if (function_exists('mb_internal_encoding')) mb_internal_encoding('UTF-8');

require_once __DIR__ . '/site.php';

/** Prepisy ciest (len testy nastavujú $GLOBALS['CONFIG']); na hostingu platia predvolené hodnoty. */
function cfg(string $key, mixed $default = null): mixed
{
    return $GLOBALS['CONFIG'][$key] ?? $default;
}

/**
 * Dáta mimo webu, ak existuje zapisovateľný priečinok ../alfa-data (vytvorí sa cez SFTP, keď by nginx
 * hostingu servíroval .json priamo a .htaccess by ich nechránil — viď NASADENIE.md), inak ./data.
 */
function default_data_dir(string $root): string
{
    $outside = dirname($root) . '/alfa-data';
    return is_dir($outside) && is_writable($outside) ? $outside : $root . '/data';
}

function data_dir(): string { return cfg('data_dir') ?? default_data_dir(ROOT); }
function uploads_dir(): string { return cfg('uploads_dir', ROOT . '/uploads'); }
function install_token_file(): string { return cfg('install_token_file', ROOT . '/install-token.php'); }

/** Vytvorí data/ (s ochranným .htaccess) a uploads/ — tieto priečinky nie sú v gite. */
function ensure_storage(): void
{
    $dirs = [
        data_dir() => "Require all denied\n",
        data_dir() . '/backup' => null,
        data_dir() . '/sessions' => null,
        uploads_dir() => null, // PHP v uploads/ blokuje koreňový .htaccess (Options/RemoveHandler tu môžu dať chybu 500)
        uploads_dir() . '/posts' => null,
        uploads_dir() . '/albums' => null,
    ];
    foreach ($dirs as $dir => $htaccess) {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Nepodarilo sa vytvoriť priečinok $dir");
        }
        if ($htaccess !== null && !is_file("$dir/.htaccess")) {
            file_put_contents("$dir/.htaccess", $htaccess);
        }
    }
}

/** HTTPS priamo alebo cez proxy hostingu (Websupport má pred Apache nginx). */
function request_is_https(?array $server = null): bool
{
    $s = $server ?? $_SERVER;
    if (!empty($s['HTTPS']) && strtolower((string)$s['HTTPS']) !== 'off') return true;
    // rôzne proxy hlásia HTTPS rôzne — ak by žiadne nesedelo, admin by sa zacyklil v presmerovaní
    return strtolower((string)($s['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
        || strtolower((string)($s['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on'
        || strtolower((string)($s['REQUEST_SCHEME'] ?? '')) === 'https'
        || (string)($s['SERVER_PORT'] ?? '') === '443';
}

function is_local_host(?array $server = null): bool
{
    $host = (string)(($server ?? $_SERVER)['HTTP_HOST'] ?? '');
    return preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/D', $host) === 1;
}

/** Admin a install len cez HTTPS (lokálny vývoj na localhost je výnimka). */
function require_https(): void
{
    if (PHP_SAPI === 'cli' || request_is_https() || is_local_host()) return;
    header('Location: https://' . $_SERVER['HTTP_HOST'] . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}
require_once __DIR__ . '/text.php';
require_once __DIR__ . '/store.php';
require_once __DIR__ . '/upload.php';
