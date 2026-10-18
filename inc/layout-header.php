<?php
/** Spoločná hlavička verejných PHP stránok. Očakáva $page = ['title' => …, 'description' => …]. */
$fullTitle = ($page['title'] ?? '') !== ''
    ? $page['title'] . ' — ' . SITE_NAME
    : SITE_NAME . ' — ITF Taekwon-Do';
send_security_headers();
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($fullTitle) ?></title>
  <?= seo_head($page, $fullTitle) ?>
  <link rel="stylesheet" href="<?= e(asset('css/fonts.css')) ?>">
  <script>document.documentElement.className += " js";</script>
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
  <?php if (SITE_DEMO): ?><div class="demo-flag">Náhľad webu (demo) — obsah a fotky sú ukážkové</div><?php endif; ?>
  <header class="site-header">
    <div class="container nav">
      <a class="brand" href="index.php"><img src="assets/logo-mark.jpg" alt="ALFA Taekwondo"><span class="brand-txt"><b>ALFA</b><span>Taekwondo · Bratislava</span></span></a>
      <button class="nav-toggle" aria-label="Menu" aria-expanded="false"><span></span><span></span><span></span></button>
      <ul class="nav-links">
        <li><a href="index.php">Domov</a></li>
        <li><a href="co-je-taekwondo.php">Čo je taekwondo</a></li>
        <li><a href="pasy.php">Pásy</a></li>
        <li><a href="aktuality.php">Aktuality</a></li>
        <li><a href="galeria.php">Galéria</a></li>
        <li><a href="o-nas.php">O nás</a></li>
        <li><a href="kontakty.php">Kontakt</a></li>
        <li class="nav-cta"><a class="btn btn-primary" href="kontakty.php#prihlaska">Skúšobná hodina</a></li>
      </ul>
    </div>
  </header>
