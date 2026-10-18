<?php
declare(strict_types=1);
// Náhľad v admine: text presne tak, ako ho server uloží (po vyčistení cez rich_clean).
// mode=inline|full → jedno pole; mode=post → karta do zoznamu + celý článok aktuality.
require __DIR__ . '/../../inc/bootstrap.php';
auth_start_session();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !auth_is_logged_in() || !csrf_check($_POST['csrf'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Platnosť stránky vypršala. Obnov ju.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$mode = input_str($_POST['mode'] ?? '');
if ($mode === 'post') {
    $date = input_str($_POST['date'] ?? '');
    $p = [
        'id' => 'p_preview0',
        'title' => trim(input_str($_POST['title'] ?? '')) ?: 'Bez nadpisu',
        'date' => valid_date($date) ? $date : date('Y-m-d'),
        'excerpt' => rich_clean(input_str($_POST['excerpt'] ?? ''), true),
        'body' => rich_clean(input_str($_POST['body'] ?? '')),
        'rich' => true,
        'image' => null,
    ];
    echo json_encode(['ok' => true, 'card' => render_post_card($p), 'article' => render_post_article($p)], JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode(['ok' => true, 'html' => rich_clean(input_str($_POST['html'] ?? ''), $mode === 'inline')], JSON_UNESCAPED_UNICODE);
