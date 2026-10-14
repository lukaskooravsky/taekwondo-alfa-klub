<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
auth_require();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_post_only('password.php');
    try {
        auth_change_password(input_str($_POST['old'] ?? ''), input_str($_POST['new'] ?? ''), input_str($_POST['new2'] ?? ''));
        flash('Heslo zmenené.');
        redirect('index.php');
    } catch (ValidationException $e) {
        $errors = $e->errors;
    } catch (RuntimeException $e) {
        error_log($e->getMessage());
        $errors['form'] = 'Heslo sa nepodarilo uložiť, skús znova.';
    }
}

admin_header('Zmena hesla');
?>
<?= isset($errors['form']) ? '<div class="flash flash-error">' . e($errors['form']) . '</div>' : '' ?>
<form method="post" class="a-form a-narrow">
  <?= csrf_field() ?>
  <label>Súčasné heslo <?= field_error($errors, 'old') ?><input type="password" name="old" required autocomplete="current-password"></label>
  <label>Nové heslo <small>(aspoň 10 znakov)</small> <?= field_error($errors, 'new') ?><input type="password" name="new" required minlength="10" autocomplete="new-password"></label>
  <label>Nové heslo znova <?= field_error($errors, 'new2') ?><input type="password" name="new2" required minlength="10" autocomplete="new-password"></label>
  <div class="a-actions"><button class="a-btn a-btn-primary">Uložiť</button><a class="a-btn" href="index.php">Zrušiť</a></div>
</form>
<?php
admin_footer();
