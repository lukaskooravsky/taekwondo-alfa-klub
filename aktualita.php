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

$img = post_image($post);
$page = ['title' => $post['title'], 'description' => post_excerpt_text($post)];
require __DIR__ . '/inc/layout-header.php';
?>

  <section class="page-hero">
    <div class="container">
      <div class="breadcrumb"><a href="index.php">Domov</a> / <a href="aktuality.php">Aktuality</a></div>
      <span class="eyebrow"><?= e(format_date_sk($post['date'])) ?></span>
      <h1 class="h1-post"><?= e($post['title']) ?></h1>
    </div>
  </section>

  <section>
    <div class="container article">
      <?php if (!empty($post['image'])): ?>
      <figure class="article-img"><img src="<?= e($img['full']) ?>" alt="<?= e($post['title']) ?>"></figure>
      <?php endif; ?>
      <p class="article-lead"><?= post_excerpt_html($post) ?></p>
      <div class="prose"><?= post_body_html($post) ?></div>
      <p style="margin-top:2.4rem;"><a class="btn btn-ghost" href="aktuality.php">← Všetky aktuality</a></p>
    </div>
  </section>

<?php require __DIR__ . '/inc/layout-footer.php';
