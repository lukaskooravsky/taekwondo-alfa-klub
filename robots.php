<?php
declare(strict_types=1);
// robots.txt (cez .htaccess). SITE_INDEX=false → nič neindexovať (dočasná ukážka), inak povoliť aj AI vyhľadávače.
require __DIR__ . '/inc/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');

if (!SITE_INDEX) {
    echo "User-agent: *\nDisallow: /\n";
    exit;
}
$disallow = "Disallow: /admin/\nDisallow: /install.php\n";
echo "User-agent: *\nAllow: /\n$disallow\n";
foreach (SEO_AI_BOTS as $bot) echo "User-agent: $bot\nAllow: /\n$disallow\n";
echo 'Sitemap: ' . site_abs('sitemap.xml') . "\n";
