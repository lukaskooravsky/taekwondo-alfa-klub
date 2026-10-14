<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';

if (auth_is_logged_in()) redirect('index.php');

$error = auth_hash() === '' ? 'Administrácia ešte nemá nastavené heslo — spusti inštaláciu (NASADENIE.md).' : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Platnosť formulára vypršala, skús to znova.';
    } else {
        try {
            $r = auth_attempt(input_str($_POST['password'] ?? ''), auth_client_key());
            if ($r === 'ok') redirect('index.php');
            $error = $r === 'locked' ? 'Príliš veľa pokusov. Skús to znova o 15 minút.' : 'Nesprávne heslo.';
        } catch (StoreException $e) {
            error_log($e->getMessage());
            $error = 'Prihlásenie sa teraz nepodarilo, skús znova.';
        }
    }
}

admin_header('Prihlásenie', false);
?>
<form method="post" class="a-form a-narrow">
  <?= csrf_field() ?>
  <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
  <label>Heslo<input type="password" name="password" required autofocus autocomplete="current-password"></label>
  <button class="a-btn a-btn-primary">Prihlásiť</button>
</form>
<?php
admin_footer();
