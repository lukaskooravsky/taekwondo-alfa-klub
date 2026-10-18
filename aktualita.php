<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

try {
    $post = post_get(input_str($_GET['id'] ?? ''));
} catch (StoreException $e) {
    error_log($e->getMessage());
    $post = null;
}
if (!$post || (is_draft($post) && !admin_viewer())) {
    not_found('Táto aktualita neexistuje alebo bola zmazaná.', 'aktuality.php', '← Všetky aktuality');
}

$page = ['title' => $post['title'], 'description' => post_excerpt_text($post), 'type' => 'article',
    'path' => 'aktualita.php?id=' . rawurlencode($post['id']), 'image' => post_image($post)['full'], 'jsonld' => [post_jsonld($post)],
    'noindex' => is_draft($post)];
require __DIR__ . '/inc/layout-header.php';
?>
<?php if (is_draft($post)): ?><div class="draft-flag">Koncept — návštevníci ho nevidia. <a href="admin/post-edit.php?id=<?= e(rawurlencode($post['id'])) ?>">Upraviť / zverejniť v administrácii</a></div><?php endif; ?>

<?= render_post_article($post) ?>

<?php require __DIR__ . '/inc/layout-footer.php';
