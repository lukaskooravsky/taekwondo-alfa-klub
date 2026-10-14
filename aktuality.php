<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

try {
    $posts = posts_all();
} catch (StoreException $e) {
    error_log($e->getMessage());
    $posts = [];
}
$page = ['title' => 'Aktuality', 'description' => 'Novinky zo života klubu ALFA Taekwondo — nábory, skúšky pásov, súťaže a podujatia.'];
require __DIR__ . '/inc/layout-header.php';
?>

  <section class="page-hero">
    <span class="kr-bg" aria-hidden="true">소식</span>
    <div class="container">
      <div class="breadcrumb"><a href="index.php">Domov</a> / Aktuality</div>
      <span class="eyebrow">Zo života klubu</span>
      <h1>Aktuality</h1>
      <p>Novinky, pozvánky a úspechy nášho klubu.</p>
    </div>
  </section>

  <section>
    <div class="container">
      <?php if ($posts): ?>
      <div class="posts">
        <?php foreach ($posts as $p) echo render_post_card($p); ?>
      </div>
      <?php else: ?>
      <p class="empty">Zatiaľ tu nie sú žiadne aktuality.</p>
      <?php endif; ?>
    </div>
  </section>

  <section class="cta-band">
    <span class="kr-huge" aria-hidden="true">태권도</span>
    <div class="container cta-inner reveal">
      <h2>Chceš byť pri tom?</h2>
      <p>Pridaj sa do klubu ALFA a buď súčasťou našich ďalších úspechov.</p>
      <a class="btn btn-primary" href="kontakty.html#prihlaska">Prihlásiť sa na skúšobnú hodinu →</a>
    </div>
  </section>

<?php require __DIR__ . '/inc/layout-footer.php';
