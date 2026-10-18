<?php
declare(strict_types=1);
// robots.txt (cez .htaccess). SITE_INDEX=false → noindex rieši meta/X-Robots-Tag (dočasná ukážka), inak sitemap a aj AI vyhľadávače.
require __DIR__ . '/inc/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');

if (!SITE_INDEX) {
    // neblokovať — roboty musia stránky načítať, aby videli noindex (meta + X-Robots-Tag); zablokované URL by Google
    // mohol zaradiť aj bez obsahu
    echo "User-agent: *\nAllow: /\n";
    exit;
}
$disallow = "Disallow: /admin/\nDisallow: /install.php\n";
echo "User-agent: *\nAllow: /\n$disallow\n";
foreach (SEO_AI_BOTS as $bot) echo "User-agent: $bot\nAllow: /\n$disallow\n";
echo 'Sitemap: ' . site_abs('sitemap.xml') . "\n";
