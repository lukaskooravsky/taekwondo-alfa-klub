<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
auth_require();

$errors = [];
$user = auth_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_post_only('password.php');
    $user = input_str($_POST['user'] ?? '');
    try {
        auth_change_credentials(input_str($_POST['old'] ?? ''), $user, input_str($_POST['new'] ?? ''), input_str($_POST['new2'] ?? ''));
        flash('Prihlasovacie údaje zmenené.');
        redirect('index.php');
    } catch (ValidationException $e) {
        $errors = $e->errors;
    } catch (RuntimeException $e) {
        error_log($e->getMessage());
        $errors['form'] = 'Údaje sa nepodarilo uložiť, skús znova.';
    }
}

admin_header('Meno a heslo');
?>
<?= isset($errors['form']) ? '<div class="flash flash-error">' . e($errors['form']) . '</div>' : '' ?>
<form method="post" class="a-form a-narrow">
  <?= csrf_field() ?>
  <label>Súčasné heslo <?= field_error($errors, 'old') ?><input type="password" name="old" required autocomplete="current-password"></label>
  <label>Prihlasovacie meno <small>(<?= USER_MIN ?>–<?= USER_MAX ?> znakov: malé písmená bez diakritiky, číslice, . _ -)</small> <?= field_error($errors, 'user') ?><input type="text" name="user" value="<?= e($user) ?>" required autocomplete="username" autocapitalize="none" spellcheck="false"></label>
  <label>Nové heslo <small>(nechaj prázdne, ak meníš len meno)</small> <?= field_error($errors, 'new') ?><input type="password" name="new" minlength="<?= PASSWORD_MIN ?>" autocomplete="new-password" data-pw-check></label>
  <label>Nové heslo znova <?= field_error($errors, 'new2') ?><input type="password" name="new2" minlength="<?= PASSWORD_MIN ?>" autocomplete="new-password"></label>
  <div class="a-actions"><button class="a-btn a-btn-primary">Uložiť</button><a class="a-btn" href="index.php">Zrušiť</a></div>
</form>
<?php
admin_footer();
