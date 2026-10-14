<?php
declare(strict_types=1);
// Spustenie: php tests/run.php [filter]   (filter = časť názvu súboru, napr. "posts")
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler(function (int $no, string $str, string $file, int $line): bool {
    if (!(error_reporting() & $no)) return false; // potlačené cez @
    throw new ErrorException($str, 0, $no, $file, $line);
});

$TMP = sys_get_temp_dir() . '/alfa-tests-' . bin2hex(random_bytes(4));
$GLOBALS['TMP'] = $TMP;
$GLOBALS['CONFIG'] = ['data_dir' => "$TMP/data", 'uploads_dir' => "$TMP/uploads", 'install_token_file' => "$TMP/install-token.php"];
require __DIR__ . '/../inc/bootstrap.php';

$GLOBALS['TESTS'] = [];
function test(string $name, callable $fn): void { $GLOBALS['TESTS'][] = [$name, $fn]; }

final class AssertionFailed extends Exception {}
function ok($cond, string $msg = 'očakávané true'): void { if (!$cond) throw new AssertionFailed($msg); }
function eq($expected, $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new AssertionFailed(($msg !== '' ? "$msg: " : '') . 'očakávané ' . var_export($expected, true) . ', je ' . var_export($actual, true));
    }
}
function throws(callable $fn, string $class): Throwable
{
    try { $fn(); } catch (Throwable $e) {
        if ($e instanceof $class) return $e;
        throw new AssertionFailed("očakávaná $class, prišla " . get_class($e) . ': ' . $e->getMessage());
    }
    throw new AssertionFailed("očakávaná výnimka $class");
}
function rrmdir(string $dir): void
{
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) { $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname()); }
    rmdir($dir);
}
function tmp(string $name): string { return $GLOBALS['TMP'] . '/' . $name; }
/** Platný PNG 1×1. */
function fixture_png(string $path): string
{
    file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    return $path;
}
/** Väčší PNG cez GD (na test zmenšovania na serveri). */
function fixture_big_png(string $path, int $w, int $h): string
{
    $img = imagecreatetruecolor($w, $h);
    imagefill($img, 0, 0, imagecolorallocate($img, 200, 160, 78));
    imagepng($img, $path);
    return $path;
}
/** Simulovaný záznam z $_FILES. */
function fake_file(string $path, string $name = 'foto.png'): array
{
    return ['name' => $name, 'type' => 'application/octet-stream', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => filesize($path)];
}

$filter = $argv[1] ?? '';
foreach (glob(__DIR__ . '/test_*.php') as $file) {
    if ($filter === '' || str_contains(basename($file), $filter)) require $file;
}

$pass = 0;
$fail = 0;
foreach ($GLOBALS['TESTS'] as [$name, $fn]) {
    rrmdir($TMP);
    mkdir($TMP, 0775, true);
    $_SESSION = [];
    $_FILES = [];
    try {
        $fn();
        $pass++;
        echo "  ok    $name\n";
    } catch (Throwable $e) {
        $fail++;
        echo "  FAIL  $name\n        " . get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ")\n";
    }
}
rrmdir($TMP);
echo "\n$pass ok, $fail zlyhalo\n";
exit($fail ? 1 : 0);
