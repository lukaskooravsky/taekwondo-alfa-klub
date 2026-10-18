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

/** Hodnoty filtra z adresy (?q=&rok=&stav=&zoradit=). */
function admin_filter_params(): array
{
    $sort = input_str($_GET['zoradit'] ?? '');
    return [
        'q' => mb_substr(trim(input_str($_GET['q'] ?? '')), 0, 100),
        'year' => preg_match('/^\d{4}$/', $y = input_str($_GET['rok'] ?? '')) ? $y : '',
        'flag' => in_array($f = input_str($_GET['stav'] ?? ''), ['0', '1'], true) ? $f : '',
        'sort' => in_array($sort, ['new', 'old', 'az'], true) ? $sort : 'new',
    ];
}

/**
 * Lišta vyhľadávania a filtra nad zoznamom. Funguje aj bez JS (GET formulár); admin.js filtruje hneď pri písaní.
 * $flags = [label filtra, text pre '1', text pre '0'].
 */
function admin_filter_bar(array $f, array $years, array $flags, int $shown, int $total, string $placeholder): string
{
    $opt = fn(string $v, string $label, string $cur) => '<option value="' . e($v) . '"' . ($v === $cur ? ' selected' : '') . '>' . e($label) . '</option>';
    $yearOpts = $opt('', 'Všetky roky', $f['year']);
    foreach ($years as $y) $yearOpts .= $opt((string)$y, (string)$y, $f['year']);
    $active = $f['q'] !== '' || $f['year'] !== '' || $f['flag'] !== '' || $f['sort'] !== 'new';
    return '<form class="a-filter" method="get" role="search" data-filter>'
        . '<label class="a-filter-q"><span class="a-sr">Hľadať</span>'
        . '<input type="search" name="q" value="' . e($f['q']) . '" placeholder="' . e($placeholder) . '" autocomplete="off" enterkeyhint="search"></label>'
        . '<label><span class="a-sr">Rok</span><select name="rok">' . $yearOpts . '</select></label>'
        . '<label><span class="a-sr">' . e($flags[0]) . '</span><select name="stav">'
        . $opt('', $flags[0] . ': všetky', $f['flag']) . $opt('1', $flags[1], $f['flag']) . $opt('0', $flags[2], $f['flag']) . '</select></label>'
        . '<label><span class="a-sr">Zoradiť</span><select name="zoradit">'
        . $opt('new', 'Najnovšie', $f['sort']) . $opt('old', 'Najstaršie', $f['sort']) . $opt('az', 'Podľa názvu A–Z', $f['sort']) . '</select></label>'
        . '<button class="a-btn" data-filter-submit>Filtrovať</button>'
        . '<a class="a-link a-filter-reset" href="?"' . ($active ? '' : ' hidden') . '>Zrušiť filter</a>'
        . '<p class="a-filter-count" data-filter-count aria-live="polite">Zobrazené ' . $shown . ' z ' . $total . '</p>'
        . '</form>';
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

/** aria-current pre položku menu aktuálnej stránky adminu. */
function admin_current(array $pages): string
{
    return in_array(basename((string)($_SERVER['SCRIPT_NAME'] ?? '')), $pages, true) ? ' aria-current="page"' : '';
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
    <a href="posts.php"<?= admin_current(['posts.php', 'post-edit.php']) ?>>Aktuality</a>
    <a href="albums.php"<?= admin_current(['albums.php', 'album.php']) ?>>Galéria</a>
    <a href="password.php"<?= admin_current(['password.php']) ?>>Nastavenia</a>
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
