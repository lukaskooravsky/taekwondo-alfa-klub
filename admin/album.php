<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
auth_require();

try {
    $album = album_get(input_str($_GET['id'] ?? $_POST['id'] ?? ''));
} catch (StoreException $e) {
    error_log($e->getMessage());
    flash('Dáta sa nepodarilo načítať — obnov zálohu z data/backup/.', 'error');
    redirect('albums.php');
}
if (!$album) {
    flash('Album sa nenašiel.', 'error');
    redirect('albums.php');
}
$self = 'album.php?id=' . rawurlencode($album['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_post_only($self);
    $photoId = input_str($_POST['photo'] ?? '');
    try {
        switch ($_POST['action'] ?? '') {
            case 'rename':
                $saved = album_update($album['id'], $_POST, status_from_request($_POST));
                if ($saved && is_draft($saved)) flash('Uložené ako koncept — na webe sa zatiaľ nezobrazuje.');
                elseif ($saved && !$saved['photos']) flash('Zverejnené — na webe sa ukáže, keď doň pridáš fotky.');
                else flash('Uložené a zverejnené.', 'ok', '../galeria.php?album=' . rawurlencode($album['id']));
                break;
            case 'cover':
                album_set_cover($album['id'], $photoId) ? flash('Titulná fotka nastavená.') : flash('Fotka sa nenašla.', 'error');
                break;
            case 'delete-photo':
                album_delete_photo($album['id'], $photoId) ? flash('Fotka zmazaná.') : flash('Fotka sa nenašla.', 'error');
                break;
            case 'delete-album':
                album_delete($album['id']);
                flash('Album zmazaný.');
                redirect('albums.php');
        }
    } catch (ValidationException $e) {
        flash(implode(' ', $e->errors), 'error');
    } catch (StoreException $e) {
        error_log($e->getMessage());
        flash('Nepodarilo sa uložiť, skús znova.', 'error');
    }
    redirect($self);
}

$cover = album_cover($album);
admin_header($album['title']);
?>
<p><a href="albums.php">← Všetky albumy</a><?php if ($album['photos']): ?> · <a href="../galeria.php?album=<?= e(rawurlencode($album['id'])) ?>" target="_blank">Pozrieť na webe ↗</a><?php endif; ?></p>

<div class="a-upload" data-album-upload data-album="<?= e($album['id']) ?>" data-endpoint="api/upload.php">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label class="a-btn a-btn-primary">+ Pridať fotky<input type="file" accept="image/*" multiple hidden></label>
  <p class="a-hint" data-upload-status>Môžeš vybrať aj viac fotiek naraz alebo ich sem pretiahnuť myšou.</p>
  <p class="a-hint">Formáty: JPG, PNG, WebP — aj GIF, AVIF či BMP (prehliadač ich prevedie). HEIC z iPhonu najprv ulož ako JPG.</p>
  <ul class="a-upload-fails" data-upload-fails></ul>
  <noscript><p class="flash flash-error">Na nahrávanie fotiek treba zapnutý JavaScript.</p></noscript>
</div>

<?php if (!$album['photos']): ?><p class="a-empty">Album je zatiaľ prázdny — na webe sa nezobrazí, kým doň nepridáš fotky.</p><?php endif; ?>
<div class="a-photos">
  <?php foreach ($album['photos'] as $ph): $isCover = $cover && $cover['id'] === $ph['id']; ?>
  <figure class="a-photo<?= $isCover ? ' is-cover' : '' ?>">
    <img src="../<?= e(upload_url($ph['thumb'])) ?>" alt="" loading="lazy">
    <?php if ($isCover): ?><span class="a-badge">Titulná</span><?php endif; ?>
    <div class="a-actions">
      <?php if (!$isCover): ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="cover"><input type="hidden" name="photo" value="<?= e($ph['id']) ?>"><button class="a-btn">Nastaviť ako titulnú</button></form>
      <?php endif; ?>
      <form method="post" data-confirm="Naozaj? Klikni znova"><?= csrf_field() ?><input type="hidden" name="action" value="delete-photo"><input type="hidden" name="photo" value="<?= e($ph['id']) ?>"><button class="a-btn a-btn-danger">Zmazať</button></form>
    </div>
  </figure>
  <?php endforeach; ?>
</div>

<section class="a-section">
  <h2>Nastavenia albumu</h2>
  <form method="post" class="a-form" data-unsaved>
    <?= csrf_field() ?><input type="hidden" name="action" value="rename"><input type="hidden" name="rich" value="1">
    <label>Názov albumu<input name="title" value="<?= e($album['title']) ?>" maxlength="120" required></label>
    <label>Dátum<input type="date" name="date" value="<?= e($album['date']) ?>" required></label>
    <div class="a-field"><span>Popis albumu <small>(nepovinný, zobrazí sa nad fotkami)</small></span>
      <textarea name="description" rows="5" data-rich="full" aria-label="Popis albumu"><?= e($album['description'] ?? '') ?></textarea></div>
    <p class="a-status"><?= is_draft($album) ? '<span class="a-badge">Koncept</span> Na webe sa zatiaľ nezobrazuje.' : '<span class="a-badge a-badge-ok">Zverejnené</span>' . ($album['photos'] ? ' Je na webe.' : ' Na webe sa ukáže, keď pridáš fotky.') ?></p>
    <div class="a-actions">
      <?php if (is_draft($album)): ?>
      <button class="a-btn">Uložiť koncept</button>
      <button class="a-btn a-btn-primary" name="publish" value="1">Zverejniť album</button>
      <?php else: ?>
      <button class="a-btn a-btn-primary">Uložiť</button>
      <button class="a-btn" name="draft" value="1">Skryť z webu (do konceptov)</button>
      <?php endif; ?>
    </div>
  </form>
</section>

<section class="a-section">
  <h2>Zmazať album</h2>
  <p class="a-hint" style="margin-bottom:.8rem;">Zmaže album aj všetky jeho fotky. Nedá sa to vrátiť späť.</p>
  <form method="post" data-confirm="Naozaj zmazať celý album? Klikni znova"><?= csrf_field() ?><input type="hidden" name="action" value="delete-album"><button class="a-btn a-btn-danger">Zmazať album</button></form>
</section>
<?php
admin_footer(rich: true);
