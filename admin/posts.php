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

$f = admin_filter_params();
$items = array_map(fn(array $p): array => [
    'fold' => text_fold($p['title'] . ' ' . post_excerpt_text($p) . ' ' . (!empty($p['rich']) ? rich_text($p['body']) : $p['body'])),
    'year' => substr($p['date'], 0, 4), 'flag' => empty($p['image']) ? '0' : '1', 'date' => $p['date'], 'title' => $p['title'], 'row' => $p,
], $posts);
$shown = admin_filter($items, $f['q'], $f['year'], $f['flag'], $f['sort']);
$years = array_values(array_unique(array_column($items, 'year')));

admin_header('Aktuality');
?>
<p><a class="a-btn a-btn-primary" href="post-edit.php">+ Nová aktualita</a></p>
<?php if ($loadError): ?><div class="flash flash-error" style="margin-top:1rem;"><?= e($loadError) ?></div><?php endif; ?>
<?php if (!$posts && !$loadError): ?><p class="a-empty">Zatiaľ žiadne aktuality.</p><?php endif; ?>
<?php if ($posts): ?>
<?= admin_filter_bar($f, $years, ['Fotka', 's fotkou', 'bez fotky'], count($shown), count($posts), 'Hľadať v nadpise a texte…') ?>
<p class="a-empty" data-filter-empty<?= $shown ? ' hidden' : '' ?>>Nič sa nenašlo. <a href="?">Zrušiť filter</a></p>
<?php endif; ?>
<ul class="a-list" data-filter-list>
  <?php $shownIds = array_column(array_column($shown, 'row'), 'id');
  foreach (admin_filter($items, '', '', '', $f['sort']) as $it): $p = $it['row']; $img = post_image($p); ?>
  <li class="a-item" data-fold="<?= e($it['fold']) ?>" data-year="<?= e($it['year']) ?>" data-flag="<?= e($it['flag']) ?>" data-date="<?= e($p['date']) ?>" data-title="<?= e(text_fold($p['title'])) ?>"<?= in_array($p['id'], $shownIds, true) ? '' : ' hidden' ?>>
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
