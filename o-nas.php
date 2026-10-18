<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$page = ['title' => 'O nás', 'description' => 'O klube ALFA Taekwondo Bratislava, trénerovi Ľubomírovi Závodnom (IV. Dan, Majster Európy) a rozvrhu tréningov pre deti a dospelých.'];
require __DIR__ . '/inc/layout-header.php';
?>

  <section class="page-hero">
    <span class="kr-bg" aria-hidden="true">알파</span>
    <div class="container">
      <div class="breadcrumb"><a href="index.php">Domov</a> / O nás</div>
      <span class="eyebrow">Klub ALFA</span>
      <h1>O nás</h1>
      <p>Sme klub ITF Taekwon-Do v bratislavskej Petržalke. Vytvárame priateľské prostredie, kde sa deti aj dospelí učia bojové umenie, získavajú kondíciu a budujú charakter.</p>
    </div>
  </section>

  <section>
    <div class="container split">
      <div class="split-media reveal"><img src="assets/extrakt.jpg" width="966" height="760" alt="Tréner Ľubomír Závodný"></div>
      <div class="reveal">
        <span class="eyebrow">Tréner</span>
        <h2 class="section-title">Ľubomír Závodný</h2>
        <p class="lead"><strong class="text-gold">IV. Dan Degree čierny pás.</strong> Majster Európy a viacnásobný majster Slovenska v ITF Taekwon-Do. Svoje skúsenosti odovzdáva s dôrazom na správnu techniku, bezpečnosť a radosť z pohybu.</p>
        <ul class="ticklist">
          <li>Majster Európy v ITF Taekwon-Do</li>
          <li>Viacnásobný majster Slovenska</li>
          <li>Roky trénerskej praxe s deťmi aj dospelými</li>
          <li>Individuálny prístup ku každému cvičencovi</li>
        </ul>
      </div>
    </div>
  </section>

  <section id="rozvrh" style="background:var(--ink-2);border-block:1px solid var(--line);">
    <div class="container">
      <div class="head-block reveal">
        <span class="eyebrow">Kedy a kde</span>
        <h2 class="section-title">Rozvrh tréningov</h2>
        <p class="lead">Trénujeme dvakrát do týždňa v telocvični ZŠ Holíčska 50 v Bratislave. Skupiny sú rozdelené podľa veku a pokročilosti.</p>
      </div>
      <div class="schedule reveal">
        <div class="schedule-row head"><div>Deň</div><div>16:30 – 17:30</div><div>17:30 – 18:30</div><div>18:30 – 19:30</div></div>
        <div class="schedule-row">
          <div class="day">Utorok</div>
          <div class="slot"><b>Začiatočníci</b><span>16:30 – 17:30</span></div>
          <div class="slot"><b>Stredne pokročilí</b><span>17:30 – 18:30</span></div>
          <div class="slot"><b>Pokročilí a dospelí</b><span>18:30 – 19:30</span></div>
        </div>
        <div class="schedule-row">
          <div class="day">Štvrtok</div>
          <div class="slot"><b>Začiatočníci</b><span>16:30 – 17:30</span></div>
          <div class="slot"><b>Stredne pokročilí</b><span>17:30 – 18:30</span></div>
          <div class="slot"><b>Pokročilí a dospelí</b><span>18:30 – 19:30</span></div>
        </div>
      </div>
      <div class="cards" style="margin-top:1.4rem;">
        <div class="card reveal"><h3>Začiatočníci</h3><p>Nepotrebuješ žiadne skúsenosti. Naučíme ťa základy od úplného začiatku, vlastným tempom.</p></div>
        <div class="card reveal"><h3>Pokročilí</h3><p>Zdokonaľovanie techniky, formy (tul), sparingy a príprava na skúšky pásov a súťaže.</p></div>
      </div>
    </div>
  </section>

  <section class="cta-band">
    <span class="kr-huge" aria-hidden="true">태권도</span>
    <div class="container cta-inner reveal">
      <h2>Staň sa súčasťou klubu</h2>
      <p>Prvá hodina je zdarma a nezáväzne. Radi ťa privítame na tréningu.</p>
      <a class="btn btn-primary" href="kontakty.php#prihlaska">Prihlásiť sa na skúšobnú hodinu →</a>
    </div>
  </section>

<?php require __DIR__ . '/inc/layout-footer.php';
