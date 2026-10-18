<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

try {
    $post = post_get(input_str($_GET['id'] ?? ''));
} catch (StoreException $e) {
    error_log($e->getMessage());
    $post = null;
}
if (!$post) not_found('Táto aktualita neexistuje alebo bola zmazaná.', 'aktuality.php', '← Všetky aktuality');

$page = ['title' => $post['title'], 'description' => post_excerpt_text($post), 'type' => 'article',
    'path' => 'aktualita.php?id=' . rawurlencode($post['id']), 'image' => post_image($post)['full'], 'jsonld' => [post_jsonld($post)]];
require __DIR__ . '/inc/layout-header.php';
?>

<?= render_post_article($post) ?>

<?php require __DIR__ . '/inc/layout-footer.php';
