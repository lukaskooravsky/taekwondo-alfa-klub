<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

try {
    $posts = posts_all();
} catch (StoreException $e) {
    error_log($e->getMessage());
    $posts = [];
}
$perPage = 12;
$pages = max(1, (int)ceil(count($posts) / $perPage));
$current = (int)input_str($_GET['strana'] ?? '1');
if ($current < 1 || $current > $pages) {
    if (isset($_GET['strana'])) not_found('Táto strana aktualít neexistuje.', 'aktuality.php', '← Aktuality');
    $current = 1;
}
$shown = array_slice($posts, ($current - 1) * $perPage, $perPage);
$page = ['title' => 'Aktuality' . ($current > 1 ? " — strana $current" : ''),
    'path' => 'aktuality.php' . ($current > 1 ? "?strana=$current" : ''),
    'description' => 'Novinky zo života klubu ALFA Taekwondo — nábory, skúšky pásov, súťaže a podujatia.'];
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
        <?php foreach ($shown as $p) echo render_post_card($p); ?>
      </div>
      <?php if ($pages > 1): ?>
      <nav class="pager" aria-label="Strany aktualít">
        <?php if ($current > 1): ?><a class="btn btn-ghost" href="aktuality.php<?= $current > 2 ? '?strana=' . ($current - 1) : '' ?>" rel="prev">← Novšie</a><?php endif; ?>
        <span>Strana <?= $current ?> z <?= $pages ?></span>
        <?php if ($current < $pages): ?><a class="btn btn-ghost" href="aktuality.php?strana=<?= $current + 1 ?>" rel="next">Staršie →</a><?php endif; ?>
      </nav>
      <?php endif; ?>
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
      <a class="btn btn-primary" href="kontakty.php#prihlaska">Prihlásiť sa na skúšobnú hodinu →</a>
    </div>
  </section>

<?php require __DIR__ . '/inc/layout-footer.php';
