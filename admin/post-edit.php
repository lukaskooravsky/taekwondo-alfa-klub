<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
auth_require();

$id = input_str($_GET['id'] ?? $_POST['id'] ?? '');
try {
    $post = $id !== '' ? post_get($id) : null;
} catch (StoreException $e) {
    error_log($e->getMessage());
    flash('Dáta sa nepodarilo načítať — obnov zálohu z data/backup/.', 'error');
    redirect('posts.php');
}
if ($id !== '' && !$post) {
    flash('Aktualita sa nenašla.', 'error');
    redirect('posts.php');
}
$self = 'post-edit.php' . ($post ? '?id=' . rawurlencode($post['id']) : '');
$values = $post ?? ['title' => '', 'date' => date('Y-m-d'), 'excerpt' => '', 'body' => '', 'image' => null];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_post_only($self);
    $values = array_merge($values, array_map('input_str', array_intersect_key($_POST, array_flip(['title', 'date', 'excerpt', 'body']))));
    $image = null;
    try {
        post_clean($_POST);                       // najprv text, aby pri chybe neostala osirelá fotka
        $image = post_image_from_request();
        $change = $image ?? (!empty($_POST['remove_image']) ? false : null);
        if ($post) {
            $saved = post_update($post['id'], $_POST, $change);
            if ($saved === null) {                // medzitým zmazaná (napr. v inej karte)
                post_delete_files($image);
                flash('Aktualita sa nenašla.', 'error');
                redirect('posts.php');
            }
        } else {
            $saved = post_create($_POST, $image);
        }
        flash('Uložené.', 'ok', '../aktualita.php?id=' . rawurlencode($saved['id']));
        redirect('posts.php');
    } catch (ValidationException $e) {
        $errors = $e->errors;
    } catch (UploadException $e) {
        $errors['image'] = $e->getMessage();
    } catch (StoreException $e) {
        error_log($e->getMessage());
        post_delete_files($image);
        $errors['form'] = 'Nepodarilo sa uložiť, skús znova.';
    }
}

if ($post && empty($post['rich']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // staršia aktualita v prostom texte → HTML pre editor (uloží sa už ako formátovaná)
    $values['excerpt'] = rich_from_text($post['excerpt'], true);
    $values['body'] = rich_from_text($post['body']);
}

admin_header($post ? 'Upraviť aktualitu' : 'Nová aktualita');
?>
<p class="a-back"><a href="posts.php">← Všetky aktuality</a><?php if ($post): ?> · <a href="../aktualita.php?id=<?= e(rawurlencode($post['id'])) ?>" target="_blank">Pozrieť na webe ↗</a><?php endif; ?></p>
<?= isset($errors['form']) ? '<div class="flash flash-error">' . e($errors['form']) . '</div>' : '' ?>
<form method="post" enctype="multipart/form-data" class="a-form" data-unsaved>
  <?= csrf_field() ?>
  <?php if ($post): ?><input type="hidden" name="id" value="<?= e($post['id']) ?>"><?php endif; ?>
  <input type="hidden" name="rich" value="1"><!-- textarea vždy obsahuje HTML (aj bez JS editora) -->
  <label>Nadpis <?= field_error($errors, 'title') ?>
    <input name="title" value="<?= e($values['title']) ?>" maxlength="150" required></label>
  <label>Dátum <?= field_error($errors, 'date') ?>
    <input type="date" name="date" value="<?= e($values['date']) ?>" required></label>
  <div class="a-field">
    <span>Fotka <small>(nepovinná)</small></span> <?= field_error($errors, 'image') ?>
    <?php if (!empty($post['image'])): ?>
    <img class="a-preview" src="../<?= e(upload_url($post['image']['thumb'])) ?>" alt="">
    <label class="a-check"><input type="checkbox" name="remove_image" value="1"> Odstrániť fotku</label>
    <?php endif; ?>
    <input type="file" name="image" accept="image/*" data-resize>
    <input type="file" name="image_full" hidden>
    <input type="file" name="image_thumb" hidden>
    <img class="a-preview" data-resize-preview alt="" hidden>
    <small class="a-hint">Vyber fotku alebo ju sem pretiahni myšou. Formáty: JPG, PNG, WebP — aj GIF, AVIF či BMP (prehliadač ich prevedie). HEIC z iPhonu najprv ulož ako JPG.</small>
    <small class="a-hint" data-resize-status><?= $errors ? 'Ak si vyberal fotku, vyber ju prosím znova.' : '' ?></small>
  </div>
  <div class="a-field"><span>Krátky úvod <small>(1–2 vety do zoznamu, max 300 znakov)</small></span> <?= field_error($errors, 'excerpt') ?>
    <textarea name="excerpt" rows="3" data-rich="inline" data-max="300" aria-label="Krátky úvod"><?= e($values['excerpt']) ?></textarea></div>
  <div class="a-field"><span>Celý text</span> <?= field_error($errors, 'body') ?>
    <textarea name="body" rows="12" data-rich="full" aria-label="Celý text"><?= e($values['body']) ?></textarea></div>
  <div class="a-actions">
    <button class="a-btn a-btn-primary">Uložiť</button>
    <button type="button" class="a-btn" data-post-preview hidden>Náhľad aktuality</button>
    <a class="a-btn" href="posts.php">Zrušiť</a>
  </div>
</form>
<?php
admin_footer(rich: true);
