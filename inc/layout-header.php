<?php
/** Spoločná hlavička verejných PHP stránok. Očakáva $page = ['title' => …, 'description' => …]. */
$fullTitle = ($page['title'] ?? '') !== ''
    ? $page['title'] . ' — ALFA Taekwondo Club Bratislava'
    : 'ALFA Taekwondo Club Bratislava — ITF Taekwon-Do';
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($fullTitle) ?></title>
  <meta name="description" content="<?= e($page['description'] ?? '') ?>">
  <link rel="stylesheet" href="css/fonts.css">
  <script>document.documentElement.className += " js";</script>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php if (SITE_DEMO): ?><div class="demo-flag">Náhľad webu (demo) — obsah a fotky sú ukážkové</div><?php endif; ?>
  <header class="site-header">
    <div class="container nav">
      <a class="brand" href="index.php"><img src="assets/logo-mark.jpg" alt="ALFA Taekwondo"><span class="brand-txt"><b>ALFA</b><span>Taekwondo · Bratislava</span></span></a>
      <button class="nav-toggle" aria-label="Menu" aria-expanded="false"><span></span><span></span><span></span></button>
      <ul class="nav-links">
        <li><a href="index.php">Domov</a></li>
        <li><a href="co-je-taekwondo.html">Čo je taekwondo</a></li>
        <li><a href="pasy.html">Pásy</a></li>
        <li><a href="aktuality.php">Aktuality</a></li>
        <li><a href="galeria.php">Galéria</a></li>
        <li><a href="o-nas.html">O nás</a></li>
        <li><a href="kontakty.html">Kontakt</a></li>
        <li class="nav-cta"><a class="btn btn-primary" href="kontakty.html#prihlaska">Skúšobná hodina</a></li>
      </ul>
    </div>
  </header>
