<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
auth_require();

$loadError = null;
try {
    $posts = posts_all();
} catch (StoreException $e) {
    error_log($e->getMessage());
    $posts = [];
    $loadError = 'Dáta sa nepodarilo načítať — obnov zálohu z data/backup/.';
}

admin_header('Aktuality');
?>
<p><a class="a-btn a-btn-primary" href="post-edit.php">+ Nová aktualita</a></p>
<?php if ($loadError): ?><div class="flash flash-error" style="margin-top:1rem;"><?= e($loadError) ?></div><?php endif; ?>
<?php if (!$posts && !$loadError): ?><p class="a-empty">Zatiaľ žiadne aktuality.</p><?php endif; ?>
<ul class="a-list">
  <?php foreach ($posts as $p): $img = post_image($p); ?>
  <li class="a-item">
    <img src="../<?= e($img['thumb']) ?>" alt="">
    <div class="a-item-txt"><b><?= e($p['title']) ?></b><span><?= e(format_date_sk($p['date'])) ?></span></div>
    <div class="a-item-act">
      <a class="a-btn" href="post-edit.php?id=<?= e(rawurlencode($p['id'])) ?>">Upraviť</a>
      <form method="post" action="post-delete.php" data-confirm="Naozaj? Klikni znova">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($p['id']) ?>">
        <button class="a-btn a-btn-danger">Zmazať</button>
      </form>
    </div>
  </li>
  <?php endforeach; ?>
</ul>
<?php
admin_footer();
