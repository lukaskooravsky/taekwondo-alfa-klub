<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require_https();
header('X-Robots-Tag: noindex, nofollow');

function install_stop(string $msg): never
{
    http_response_code(403);
    exit($msg);
}

if (is_file(auth_file())) {
    install_stop('Inštalácia už prebehla. Heslo sa dá zmeniť v administrácii. (Zabudnuté heslo: pozri NASADENIE.md.)');
}
$token = input_str($_GET['token'] ?? $_POST['token'] ?? '');
if (!install_token_valid($token, install_token_file())) {
    install_stop('Inštalácia je zamknutá. Spusti lokálne „php tools/new-install-token.php <doména>“, nahraj install-token.php cez SFTP a otvor vypísaný odkaz.');
}

$req = install_requirements();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$req['required']) {
    try {
        if (!install_set_credentials(input_str($_POST['user'] ?? ''), input_str($_POST['password'] ?? ''), input_str($_POST['password2'] ?? ''))) {
            install_stop('Inštalácia už prebehla.');
        }
        if (!empty($_POST['seed'])) install_seed(__DIR__ . '/seed');
        if (!@unlink(install_token_file())) error_log('install-token.php sa nepodarilo zmazať — zmaž ho cez SFTP');
        header('Location: admin/login.php');
        exit;
    } catch (ValidationException $e) {
        $errors = $e->errors;
    } catch (RuntimeException $e) {
        error_log($e->getMessage());
        $errors['form'] = 'Nepodarilo sa uložiť, skontroluj práva zápisu do priečinka data/.';
    }
}
$ip = auth_client_ip();
$remote = (string)($_SERVER['REMOTE_ADDR'] ?? '');
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Inštalácia — Admin ALFA</title>
  <link rel="stylesheet" href="css/fonts.css">
  <link rel="stylesheet" href="admin/admin.css">
</head>
<body>
<main class="a-main">
  <h1>Inštalácia administrácie</h1>
  <?php if ($req['required']): ?>
  <div class="flash flash-error">Hosting nespĺňa požiadavky — chýba:<ul><?php foreach ($req['required'] as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>
  <?php foreach ($req['recommended'] as $r): ?><div class="flash flash-error">Odporúčané: <?= e($r) ?></div><?php endforeach; ?>
  <p class="a-hint" style="margin-bottom:1.2rem;">
    Tvoja zistená IP adresa: <b><?= e($ip) ?></b><?= $ip !== $remote ? ' (cez proxy hostingu, REMOTE_ADDR ' . e($remote) . ')' : '' ?>.
    Mala by sedieť s tým, čo ukáže napr. „what is my ip“ — inak blokácia po 5 zlých heslách nebude fungovať správne.
  </p>
  <?= isset($errors['form']) ? '<div class="flash flash-error">' . e($errors['form']) . '</div>' : '' ?>
  <?php if (!$req['required']): ?>
  <p class="a-hint" style="margin-bottom:1.2rem;">Nastav meno a heslo, ktorým sa bude tréner prihlasovať do administrácie.</p>
  <form method="post" class="a-form a-narrow">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <label>Prihlasovacie meno <small>(<?= USER_MIN ?>–<?= USER_MAX ?> znakov: malé písmená bez diakritiky, číslice, . _ -)</small> <?= isset($errors['user']) ? '<em class="a-err">' . e($errors['user']) . '</em>' : '' ?><input type="text" name="user" value="<?= e(input_str($_POST['user'] ?? '')) ?>" required autocomplete="username" autocapitalize="none" spellcheck="false"></label>
    <label>Heslo <?= isset($errors['password']) ? '<em class="a-err">' . e($errors['password']) . '</em>' : '' ?><input type="password" name="password" required minlength="<?= PASSWORD_MIN ?>" autocomplete="new-password" data-pw-check></label>
    <label>Heslo znova <?= isset($errors['password2']) ? '<em class="a-err">' . e($errors['password2']) . '</em>' : '' ?><input type="password" name="password2" required minlength="<?= PASSWORD_MIN ?>" autocomplete="new-password"></label>
    <label class="a-check"><input type="checkbox" name="seed" value="1" checked> Nahrať ukážkové aktuality a album</label>
    <button class="a-btn a-btn-primary">Dokončiť inštaláciu</button>
  </form>
  <?php endif; ?>
</main>
<script src="admin/admin.js"></script>
</body>
</html>
