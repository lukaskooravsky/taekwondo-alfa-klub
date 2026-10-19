<?php
declare(strict_types=1);
// Zabalí web pre trénera na nasadenie na vlastnú doménu (spúšťa sa LOKÁLNE):
//   php -d extension=zip tools/zabal-pre-trenera.php [cieľ.zip]
// ZIP: web/ (commitnuté súbory bez vývojárskych + nový jednorazový install-token.php),
//      návod na nasadenie (s tokenom) a návod na administráciu. data/ a uploads/ v ňom nie sú — vzniknú na hostingu.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (!class_exists(ZipArchive::class)) exit("Chýba rozšírenie zip — spusti: php -d extension=zip tools/zabal-pre-trenera.php\n");

$root = dirname(__DIR__);
chdir($root);
require $root . '/inc/bootstrap.php';

// balí sa súbor z disku → musí sedieť s gitom
if (trim((string)shell_exec('git status --porcelain -uno')) !== '') exit("Najprv commitni zmeny (balí sa commitnutý stav).\n");

$skip = '#^(\.git|docs/|tests/|tools/|_zaloha/|\.nojekyll$|[^/]+\.md$)#';
$files = array_filter(
    explode("\n", trim((string)shell_exec('git ls-files'))),
    fn(string $f): bool => $f !== '' && !preg_match($skip, $f)
);

$out = $argv[1] ?? dirname($root) . '/ALFA-web-' . date('Y-m-d') . '.zip';
if (is_file($out)) unlink($out);
$zip = new ZipArchive();
if ($zip->open($out, ZipArchive::CREATE) !== true) exit("Neviem vytvoriť $out\n");

foreach ($files as $f) $zip->addFile("$root/$f", "web/$f");

// jednorazový token na prvé spustenie (install.php ho po inštalácii zmaže); do repa sa nedostane
$tmp = tempnam(sys_get_temp_dir(), 'tok');
$token = install_token_write($tmp);
$zip->addFromString('web/' . basename(install_token_file()), (string)file_get_contents($tmp));
unlink($tmp);

$guide = str_replace('@@TOKEN@@', $token, (string)file_get_contents("$root/NASADENIE-PRE-TRENERA.md"));
$zip->addFromString('1 - NAVOD - ako dat web na domenu.md', $guide);
$zip->addFile("$root/NAVOD-PRE-TRENERA.md", '2 - NAVOD - administracia (aktuality, galeria).md');
$zip->close();

$commit = trim((string)shell_exec('git rev-parse --short HEAD'));
printf("Hotovo: %s (%d súborov webu, commit %s, %d kB)\n", $out, count($files) + 1, $commit, intdiv((int)filesize($out), 1024));
echo "Inštalačný odkaz: https://<doména>/install.php?token=$token\n";
