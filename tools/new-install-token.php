<?php
declare(strict_types=1);
// Spúšťa sa LOKÁLNE (na Websupporte nie je trvalé SSH):
//   php tools/new-install-token.php alfa-taekwondo.sk
// Vytvorí install-token.php v koreni repa a vypíše odkaz. Súbor nahraj cez SFTP do koreňa webu.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../inc/bootstrap.php';

$domain = $argv[1] ?? 'tvoja-domena.sk';
$scheme = str_starts_with($domain, 'localhost') ? 'http' : 'https';
$token = install_token_write(install_token_file());
echo "Vytvorený súbor: " . install_token_file() . "\n";
echo "1. Nahraj ho cez SFTP do koreňa webu (vedľa install.php).\n";
echo "2. Otvor: $scheme://$domain/install.php?token=$token\n";
echo "Token sa po úspešnej inštalácii sám zmaže.\n";
