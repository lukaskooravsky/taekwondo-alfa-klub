<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$albumId = input_str($_GET['album'] ?? '');
try {
    $album = $albumId !== '' ? album_get($albumId) : null;
    $albums = $albumId === '' ? albums_public() : [];
} catch (StoreException $e) {
    error_log($e->getMessage());
    $album = null;
    $albums = [];
}
if ($album && is_draft($album) && !admin_viewer()) $album = null;
if ($albumId !== '' && (!$album || !$album['photos'])) {
    not_found('Tento album neexistuje alebo je prázdny.', 'galeria.php', '← Všetky albumy');
}

if ($album) {
    $cover = album_cover($album);
    $desc = rich_text($album['description'] ?? '');
    $page = ['title' => $album['title'] . ' — Galéria', 'path' => 'galeria.php?album=' . rawurlencode($album['id']),
        'description' => ($desc !== '' ? $desc . ' ' : '') . 'Fotky z albumu ' . $album['title'] . ' — ' . SITE_NAME . '.',
        'image' => $cover ? upload_url($cover['full']) : SEO_DEFAULT_IMAGE];
} else {
    $page = ['title' => 'Galéria', 'description' => 'Fotky z tréningov, skúšok a súťaží klubu ALFA Taekwondo Bratislava.'];
}
require __DIR__ . '/inc/layout-header.php';
?>

  <section class="page-hero">
    <span class="kr-bg" aria-hidden="true">사진</span>
    <div class="container">
      <?php if ($album): ?>
      <div class="breadcrumb"><a href="index.php">Domov</a> / <a href="galeria.php">Galéria</a></div>
      <span class="eyebrow"><?= e(format_date_sk($album['date'])) ?> · <?= e(sk_plural(count($album['photos']), 'fotka', 'fotky', 'fotiek')) ?></span>
      <h1 class="h1-post"><?= e($album['title']) ?></h1>
      <?php if (($album['description'] ?? '') !== ''): ?><div class="rich album-desc"><?= rich_clean($album['description']) ?></div><?php endif; ?>
      <p>Kliknutím sa fotka zväčší.</p>
      <?php else: ?>
      <div class="breadcrumb"><a href="index.php">Domov</a> / Galéria</div>
      <span class="eyebrow">Momentky</span>
      <h1>Galéria</h1>
      <p>Fotky z tréningov, skúšok a súťaží.</p>
      <?php endif; ?>
    </div>
  </section>

  <section>
    <div class="container">
      <?php if ($album): ?>
      <div class="gallery">
        <?php foreach ($album['photos'] as $ph) echo render_gallery_figure($ph, $album['title']); ?>
      </div>
      <p style="margin-top:2.4rem;"><a class="btn btn-ghost" href="galeria.php">← Všetky albumy</a></p>
      <?php elseif ($albums): ?>
      <div class="albums">
        <?php foreach ($albums as $a) echo render_album_tile($a); ?>
      </div>
      <?php else: ?>
      <p class="empty">Galéria sa pripravuje.</p>
      <?php endif; ?>
    </div>
  </section>

<?php require __DIR__ . '/inc/layout-footer.php';
