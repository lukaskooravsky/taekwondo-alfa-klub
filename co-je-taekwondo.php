<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$page = ['title' => 'Čo je taekwondo', 'description' => 'Čo je ITF Taekwon-Do: kórejské bojové umenie, jeho filozofia, princípy a čo tréning rozvíja u detí aj dospelých.'];
require __DIR__ . '/inc/layout-header.php';
?>

  <section class="page-hero">
    <span class="kr-bg" aria-hidden="true">태권도</span>
    <div class="container">
      <div class="breadcrumb"><a href="index.php">Domov</a> / Čo je taekwondo</div>
      <span class="eyebrow">Kórejské bojové umenie</span>
      <h1>Čo je taekwondo</h1>
      <p>Taekwon-Do (태권도) je kórejské bojové umenie, ktoré spája údery, kopy, sebaobranu a životnú filozofiu. „Tae" znamená noha, „Kwon" ruka a „Do" cesta — teda *cesta ruky a nohy*.</p>
    </div>
  </section>

  <section>
    <div class="container split">
      <div class="reveal">
        <span class="eyebrow">ITF Taekwon-Do</span>
        <h2 class="section-title">Umenie tela aj mysle</h2>
        <p class="lead">Náš klub sa venuje <strong class="text-gold">ITF Taekwon-Do</strong> (International Taekwon-Do Federation) — pôvodnej, tradičnej forme založenej generálom Choi Hong Hi. Kladie dôraz na precíznu techniku, silu vychádzajúcu z pohybu celého tela a na charakterový rozvoj cvičenca.</p>
        <p class="lead" style="margin-top:1rem;">Na tréningoch sa venujeme technikám rúk a nôh, formám (tul), sebaobrane, kondícii aj rešpektu voči sebe a ostatným.</p>
      </div>
      <div class="split-media reveal"><img src="assets/extrakt.jpg" width="966" height="760" alt="Taekwondo tréning"></div>
    </div>
  </section>

  <section style="background:var(--ink-2);border-block:1px solid var(--line);">
    <div class="container">
      <div class="head-block reveal">
        <span class="eyebrow">Päť princípov</span>
        <h2 class="section-title">Zásady Taekwon-Do</h2>
        <p class="lead">Každý tréning stojí na piatich zásadách, ktoré formujú nielen bojovníka, ale aj človeka.</p>
      </div>
      <div class="cards">
        <div class="card reveal"><div class="num">01</div><h3>Zdvorilosť</h3><p>Rešpekt a slušnosť voči trénerovi, spolucvičencom aj sebe samému.</p></div>
        <div class="card reveal"><div class="num">02</div><h3>Čestnosť</h3><p>Poctivosť a úprimnosť v tréningu aj mimo telocvične.</p></div>
        <div class="card reveal"><div class="num">03</div><h3>Vytrvalosť</h3><p>Trpezlivosť a odhodlanie pri prekonávaní prekážok a napredovaní.</p></div>
        <div class="card reveal"><div class="num">04</div><h3>Sebaovládanie</h3><p>Kontrola tela aj emócií — základ zodpovedného používania techník.</p></div>
        <div class="card reveal"><div class="num">05</div><h3>Nezlomný duch</h3><p>Odvaha stáť si za svojím a nikdy sa nevzdať.</p></div>
      </div>
    </div>
  </section>

  <section>
    <div class="container">
      <div class="head-block reveal">
        <span class="eyebrow">Pre koho</span>
        <h2 class="section-title">Taekwondo je pre každého</h2>
      </div>
      <div class="cards">
        <div class="card reveal"><h3>Deti</h3><p>Zdravý pohyb, disciplína a sebadôvera hravou formou. Deti sa učia rešpektu a sústredeniu.</p></div>
        <div class="card reveal"><h3>Dospelí</h3><p>Skvelá kondícia, odbúranie stresu a nové zručnosti — bez ohľadu na vek či doterajšie skúsenosti.</p></div>
        <div class="card reveal"><h3>Začiatočníci</h3><p>Začíname od úplných základov. Nepotrebuješ žiadnu prípravu — len chuť hýbať sa.</p></div>
        <div class="card reveal"><h3>Pokročilí</h3><p>Zdokonaľovanie techniky, príprava na skúšky pásov a súťaže pod odborným vedením.</p></div>
      </div>
      <div style="margin-top:2.6rem;"><a class="btn btn-primary" href="kontakty.php#prihlaska">Vyskúšaj prvú hodinu zdarma →</a></div>
    </div>
  </section>

<?php require __DIR__ . '/inc/layout-footer.php';
