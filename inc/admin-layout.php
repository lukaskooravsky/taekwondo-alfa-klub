<?php
declare(strict_types=1);

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function flash(string $msg, string $type = 'ok', ?string $link = null): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg, 'link' => $link];
}

function flash_html(): string
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    if (!$f) return '';
    $link = $f['link'] ? ' <a href="' . e($f['link']) . '" target="_blank">Pozrieť na webe ↗</a>' : '';
    return '<div class="flash flash-' . e($f['type']) . '">' . e($f['msg']) . $link . '</div>';
}

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<em class="a-err">' . e($errors[$key]) . '</em>' : '';
}

/** Spoločná kontrola POST formulárov adminu. */
function admin_post_only(string $back): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Nepovolená metóda.');
    }
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('Súbor je príliš veľký pre server (limit ' . ini_get('post_max_size') . '). Skús menšiu fotku.', 'error');
        redirect($back);
    }
    if (!csrf_check($_POST['csrf'] ?? null)) {
        flash('Platnosť formulára vypršala, skús to znova.', 'error');
        redirect($back);
    }
}

function admin_header(string $title, bool $nav = true): void
{
    ?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> — Admin ALFA</title>
  <link rel="stylesheet" href="../css/fonts.css">
  <link rel="stylesheet" href="admin.css">
</head>
<body>
<header class="a-top">
  <a class="a-brand" href="index.php"><img src="../assets/logo-mark.jpg" alt=""><span>ALFA admin</span></a>
  <?php if ($nav): ?>
  <nav class="a-nav">
    <a href="posts.php">Aktuality</a>
    <a href="albums.php">Galéria</a>
    <a href="../index.php" target="_blank">Web ↗</a>
    <form method="post" action="logout.php"><?= csrf_field() ?><button class="a-link">Odhlásiť</button></form>
  </nav>
  <?php endif; ?>
</header>
<main class="a-main">
  <h1><?= e($title) ?></h1>
  <?= flash_html() ?>
<?php
}

/** $rich = stránka má formátovací editor (Quill sa načíta len tam, kde treba). */
function admin_footer(bool $rich = false): void
{
    ?>
</main>
<?php if ($rich): ?>
<link rel="stylesheet" href="../assets/vendor/quill/quill.snow.css">
<script src="../assets/vendor/quill/quill.js"></script>
<?php endif; ?>
<script src="admin.js"></script>
</body>
</html>
<?php
}
