<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
auth_require();

$errors = [];
$values = ['title' => '', 'date' => date('Y-m-d')];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_post_only('albums.php');
    try {
        $a = album_create($_POST);
        flash('Album vytvorený. Pridaj doň fotky.');
        redirect('album.php?id=' . rawurlencode($a['id']));
    } catch (ValidationException $e) {
        $errors = $e->errors;
        $values = ['title' => input_str($_POST['title'] ?? ''), 'date' => input_str($_POST['date'] ?? '')];
    } catch (StoreException $e) {
        error_log($e->getMessage());
        $errors['form'] = 'Nepodarilo sa uložiť, skús znova.';
    }
}

$loadError = null;
try {
    $albums = albums_all();
} catch (StoreException $e) {
    error_log($e->getMessage());
    $albums = [];
    $loadError = 'Dáta sa nepodarilo načítať — obnov zálohu z data/backup/.';
}

$f = admin_filter_params();
$items = array_map(fn(array $a): array => [
    'fold' => text_fold($a['title'] . ' ' . rich_text($a['description'] ?? '')),
    'year' => substr($a['date'], 0, 4), 'flag' => $a['photos'] ? '1' : '0', 'date' => $a['date'], 'title' => $a['title'], 'row' => $a,
], $albums);
$shown = admin_filter($items, $f['q'], $f['year'], $f['flag'], $f['sort']);
$years = array_values(array_unique(array_column($items, 'year')));

admin_header('Galéria');
?>
<?php if ($loadError): ?><div class="flash flash-error"><?= e($loadError) ?></div><?php endif; ?>
<?= isset($errors['form']) ? '<div class="flash flash-error">' . e($errors['form']) . '</div>' : '' ?>
<details class="a-upload" <?= $errors ? 'open' : '' ?>>
  <summary class="a-btn a-btn-primary">+ Nový album</summary>
  <form method="post" class="a-form" style="margin-top:1rem;">
    <?= csrf_field() ?>
    <label>Názov albumu <?= field_error($errors, 'title') ?><input name="title" value="<?= e($values['title']) ?>" maxlength="120" required></label>
    <label>Dátum <?= field_error($errors, 'date') ?><input type="date" name="date" value="<?= e($values['date']) ?>" required></label>
    <div class="a-actions"><button class="a-btn a-btn-primary">Vytvoriť album</button></div>
  </form>
</details>
<?php if (!$albums && !$loadError): ?><p class="a-empty">Zatiaľ žiadne albumy.</p><?php endif; ?>
<?php if ($albums): ?>
<?= admin_filter_bar($f, $years, ['Fotky', 's fotkami', 'prázdne (na webe sa nezobrazia)'], count($shown), count($albums), 'Hľadať v názve a popise…') ?>
<p class="a-empty" data-filter-empty<?= $shown ? ' hidden' : '' ?>>Nič sa nenašlo. <a href="?">Zrušiť filter</a></p>
<?php endif; ?>
<ul class="a-list" data-filter-list>
  <?php $shownIds = array_column(array_column($shown, 'row'), 'id');
  foreach (admin_filter($items, '', '', '', $f['sort']) as $it): $a = $it['row']; $cover = album_cover($a); ?>
  <li class="a-item" data-fold="<?= e($it['fold']) ?>" data-year="<?= e($it['year']) ?>" data-flag="<?= e($it['flag']) ?>" data-date="<?= e($a['date']) ?>" data-title="<?= e(text_fold($a['title'])) ?>"<?= in_array($a['id'], $shownIds, true) ? '' : ' hidden' ?>>
    <img src="../<?= e($cover ? upload_url($cover['thumb']) : PLACEHOLDER_IMAGE) ?>" alt="">
    <div class="a-item-txt"><b><?= e($a['title']) ?></b><span><?= e(format_date_sk($a['date'])) ?> · <?= e(sk_plural(count($a['photos']), 'fotka', 'fotky', 'fotiek')) ?></span></div>
    <div class="a-item-act"><a class="a-btn" href="album.php?id=<?= e(rawurlencode($a['id'])) ?>">Otvoriť</a></div>
  </li>
  <?php endforeach; ?>
</ul>
<?php
admin_footer();
