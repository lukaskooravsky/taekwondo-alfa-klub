<?php
declare(strict_types=1);
// sitemap.xml (cez .htaccess) — stránky, aktuality a albumy; aktualizuje sa sama.
require __DIR__ . '/inc/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');

$urls = [];
foreach (seo_pages() as [$path]) $urls[] = [site_abs($path), null];
try {
    foreach (posts_public() as $p) $urls[] = [site_abs('aktualita.php?id=' . rawurlencode($p['id'])), substr((string)($p['updated'] ?? $p['date']), 0, 10)];
    foreach (albums_public() as $a) $urls[] = [site_abs('galeria.php?album=' . rawurlencode($a['id'])), substr((string)($a['updated'] ?? $a['date']), 0, 10)];
} catch (StoreException $e) {
    error_log($e->getMessage());
}
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n", '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($urls as [$loc, $mod]) {
    echo '  <url><loc>', htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8'), '</loc>', $mod ? "<lastmod>$mod</lastmod>" : '', "</url>\n";
}
echo "</urlset>\n";
