<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';

if (auth_is_logged_in()) redirect('index.php');

$error = auth_hash() === '' || auth_user() === '' ? 'Administrácia ešte nemá nastavený e-mail a heslo — spusti inštaláciu (NASADENIE.md).' : '';
$user = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!login_csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Platnosť formulára vypršala, skús to znova.';
    } else {
        try {
            $user = input_str($_POST['user'] ?? '');
            $r = auth_attempt($user, input_str($_POST['password'] ?? ''), auth_client_key(), null, auth_is_trusted());
            if ($r === 'ok') {
                auth_cookie(TRUST_COOKIE, auth_trust_value(), 365 * 24 * 3600);
                auth_cookie(LOGIN_CSRF_COOKIE, '', 0);
                redirect('index.php');
            }
            $error = $r === 'locked' ? 'Príliš veľa pokusov. Skús to znova o 15 minút.' : 'Nesprávny e-mail alebo heslo.';
        } catch (StoreException $e) {
            error_log($e->getMessage());
            $error = 'Prihlásenie sa teraz nepodarilo, skús znova.';
        }
    }
}

admin_header('Prihlásenie', false);
?>
<form method="post" class="a-form a-narrow">
  <input type="hidden" name="csrf" value="<?= e(login_csrf_token()) ?>">
  <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
  <label>E-mail<input type="email" name="user" value="<?= e($user) ?>" required autofocus autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="254"></label>
  <label>Heslo<input type="password" name="password" required autocomplete="current-password"></label>
  <button class="a-btn a-btn-primary">Prihlásiť</button>
</form>
<?php
admin_footer();
