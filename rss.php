<?php
declare(strict_types=1);
// RSS kanál aktualít — čítačky, agregátory a AI nástroje vidia novinky klubu.
require __DIR__ . '/inc/bootstrap.php';
header('Content-Type: application/rss+xml; charset=utf-8');
if (!SITE_INDEX) header('X-Robots-Tag: noindex');

$x = fn(string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
try {
    $posts = array_slice(posts_all(), 0, 20);
} catch (StoreException $e) {
    error_log($e->getMessage());
    $posts = [];
}
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<rss version="2.0"><channel>', "\n";
echo '<title>', $x(SITE_NAME . ' — aktuality'), '</title><link>', $x(site_abs('aktuality.php')), '</link>';
echo '<description>', $x(CLUB['description']), '</description><language>sk</language>', "\n";
foreach ($posts as $p) {
    $url = site_abs('aktualita.php?id=' . rawurlencode($p['id']));
    echo '<item><title>', $x($p['title']), '</title><link>', $x($url), '</link><guid isPermaLink="true">', $x($url), '</guid>';
    echo '<pubDate>', $x(date(DATE_RSS, strtotime($p['date'] . ' 12:00'))), '</pubDate>';
    echo '<description>', $x(post_excerpt_text($p)), '</description></item>', "\n";
}
echo "</channel></rss>\n";
