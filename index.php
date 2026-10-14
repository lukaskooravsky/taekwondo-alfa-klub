<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

try {
    $latestPosts = array_slice(posts_all(), 0, 3);
    $albums = albums_public();
} catch (StoreException $e) {
    error_log($e->getMessage());
    $latestPosts = [];
    $albums = [];
}
$teaserAlbum = $albums[0] ?? null;
$teaserPhotos = $teaserAlbum ? array_slice($teaserAlbum['photos'], 0, 4) : [];
$page = ['title' => '', 'description' => 'ALFA Taekwondo Club Bratislava — ITF Taekwon-Do pod vedením Ľubomíra Závodného (IV. Dan, Majster Európy). Tréningy pre deti aj dospelých na ZŠ Holíčska 50.'];
require __DIR__ . '/inc/layout-header.php';
?>

  <!-- HERO -->
  <section class="hero">
    <div class="hero-bg" aria-hidden="true"></div>
    <img class="hero-mark" src="assets/logo-mark.jpg" alt="">
    <div class="container hero-grid">
      <div>
        <span class="eyebrow">ITF Taekwon-Do · Bratislava</span>
        <h1 class="hero-title">Taekwondo<span class="kr">태권도</span></h1>
        <p class="hero-claim">Bojové umenie na rozvoj sily, kondície a obratnosti.</p>
        <p class="lead">Klub ALFA privíta deti aj dospelých — od úplných začiatočníkov po pokročilých. Tréningy vedie <strong class="text-gold">Ľubomír Závodný</strong>, IV. Dan čierny pás a Majster Európy.</p>
        <div class="hero-actions">
          <a class="btn btn-primary" href="kontakty.html#prihlaska">Príď na skúšobnú hodinu →</a>
          <a class="btn btn-ghost" href="#rozvrh">Rozvrh tréningov</a>
        </div>
      </div>
      <figure class="hero-figure">
        <img src="assets/extrakt.png" alt="Tréning taekwonda v klube ALFA">
      </figure>
    </div>
  </section>

  <!-- INFO STRIP -->
  <div class="strip">
    <div class="container" style="padding-inline:0;">
      <div class="strip-grid">
        <div class="strip-item">
          <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.3-7-11a7 7 0 0114 0c0 4.7-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
          <div><b>ZŠ Holíčska 50</b><span>Bratislava — Petržalka</span></div>
        </div>
        <div class="strip-item">
          <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/></svg></span>
          <div><b>Utorok &amp; Štvrtok</b><span>16:30 · 17:30 · 18:30 — tréning trvá 1 hodinu</span></div>
        </div>
        <div class="strip-item">
          <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3 5 5 .8-3.7 3.5.9 5.2L12 19l-5.1 2.5.9-5.2L4 12.8 9 12z"/></svg></span>
          <div><b>IV. Dan · Majster Európy</b><span>Tréner Ľubomír Závodný</span></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ČO ZÍSKAŠ -->
  <section>
    <div class="container">
      <div class="head-block reveal">
        <span class="eyebrow">Prečo taekwondo</span>
        <h2 class="section-title">Viac než šport <span class="kr">무도</span></h2>
        <p class="lead">Taekwondo rozvíja telo aj charakter. Na tréningoch získaš kondíciu, sebadôveru a zdravé návyky do života.</p>
      </div>
      <div class="cards">
        <div class="card reveal">
          <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4v6a6 6 0 0012 0V4M4 4h4M16 4h4M9 20h6"/></svg></div>
          <h3>Sila &amp; kondícia</h3>
          <p>Komplexné cvičenie celého tela — vytrvalosť, výbušnosť a fyzická odolnosť.</p>
        </div>
        <div class="card reveal">
          <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M5 8l7-5 7 5M5 8v8l7 5 7-5V8"/></svg></div>
          <h3>Obratnosť</h3>
          <p>Koordinácia, rovnováha a flexibilita, ktoré využiješ v každom športe aj v bežnom živote.</p>
        </div>
        <div class="card reveal">
          <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-7 8-7s8 3 8 7"/></svg></div>
          <h3>Sebadisciplína</h3>
          <p>Rešpekt, sústredenie a vytrvalosť — hodnoty, ktoré taekwondo prináša do každodenného života.</p>
        </div>
        <div class="card reveal">
          <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
          <h3>Sebaobrana</h3>
          <p>Praktické techniky a reakcie, vďaka ktorým sa budeš cítiť istejšie a bezpečnejšie.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- DISCIPLÍNY -->
  <section id="discipliny" style="border-top:1px solid var(--line);">
    <div class="container">
      <div class="head-block reveal">
        <span class="eyebrow">Súťažné Taekwon-Do</span>
        <h2 class="section-title">Disciplíny ITF</h2>
        <p class="lead">ITF Taekwon-Do rozvíja technickú presnosť, rýchlosť, silu, odvahu aj tímovú spoluprácu. Na súťažiach sa stretávame v týchto disciplínach.</p>
      </div>
      <div class="disc-grid">
        <article class="disc reveal">
          <div class="disc-media" data-tag="Tul" style="background-image:url('assets/disc-tul.jpg')"></div>
          <div class="disc-body">
            <span class="disc-tag">Tul</span>
            <h3>Technické zostavy</h3>
            <p>Presne stanovené zostavy útokov a obrán. Rozhodcovia hodnotia techniku, silu, rovnováhu, rytmus a správne dýchanie.</p>
          </div>
        </article>
        <article class="disc reveal">
          <div class="disc-media" data-tag="Matsogi" style="background-image:url('assets/disc-matsogi.jpg')"></div>
          <div class="disc-body">
            <span class="disc-tag">Matsogi</span>
            <h3>Športový boj</h3>
            <p>Dynamický semi-kontaktný súboj dvoch pretekárov. Body sa získavajú za presné a kontrolované techniky na povolené pásma.</p>
          </div>
        </article>
        <article class="disc reveal">
          <div class="disc-media" data-tag="Wi-ryok" style="background-image:url('assets/disc-wiryok.jpg')"></div>
          <div class="disc-body">
            <span class="disc-tag">Wi-ryok</span>
            <h3>Silové prerážanie</h3>
            <p>Disciplína preveruje účinnosť a silu techník rúk aj nôh pri prerážaní dosiek určenými technikami.</p>
          </div>
        </article>
        <article class="disc reveal">
          <div class="disc-media" data-tag="Teuki" style="background-image:url('assets/disc-teuki.jpg')"></div>
          <div class="disc-body">
            <span class="disc-tag">Teuki</span>
            <h3>Špeciálne techniky</h3>
            <p>Kopy vo výskoku na vysoko alebo ďaleko umiestnené ciele. Rozhoduje výška, presnosť a správne vykonanie techniky.</p>
          </div>
        </article>
      </div>
    </div>
  </section>

  <!-- TRÉNER -->
  <section style="background:var(--ink-2);border-block:1px solid var(--line);">
    <div class="container split">
      <div class="split-media reveal">
        <img src="assets/extrakt.png" alt="Ľubomír Závodný">
      </div>
      <div class="reveal">
        <span class="eyebrow">Tréner</span>
        <h2 class="section-title">Ľubomír Závodný</h2>
        <p class="lead">IV. Dan Degree čierny pás, <strong class="text-gold">Majster Európy</strong> a viacnásobný majster Slovenska. Roky skúseností odovzdáva deťom aj dospelým s dôrazom na správnu techniku, bezpečnosť a radosť z pohybu.</p>
        <ul class="ticklist">
          <li>Individuálny prístup ku každému cvičencovi</li>
          <li>Tréningy pre deti aj dospelých, začiatočníkov aj pokročilých</li>
          <li>Príprava na skúšky technických stupňov a súťaže</li>
        </ul>
        <div style="margin-top:2rem;"><a class="btn btn-ghost" href="o-nas.html">Viac o klube →</a></div>
      </div>
    </div>
  </section>

  <!-- ROZVRH -->
  <section id="rozvrh">
    <div class="container">
      <div class="head-block reveal">
        <span class="eyebrow">Kedy trénujeme</span>
        <h2 class="section-title">Rozvrh tréningov</h2>
        <p class="lead">Tréningy prebiehajú dvakrát do týždňa v telocvični ZŠ Holíčska 50.</p>
      </div>
      <div class="schedule reveal">
        <div class="schedule-row head">
          <div>Deň</div><div>16:30 – 17:30</div><div>17:30 – 18:30</div><div>18:30 – 19:30</div>
        </div>
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
    </div>
  </section>

  <!-- AKTUALITY -->
  <section style="background:var(--ink-2);border-block:1px solid var(--line);">
    <div class="container">
      <div class="head-block reveal">
        <span class="eyebrow">Zo života klubu</span>
        <h2 class="section-title">Aktuality</h2>
      </div>
      <?php if ($latestPosts): ?>
      <div class="posts">
        <?php foreach ($latestPosts as $p) echo render_post_card($p); ?>
      </div>
      <?php else: ?>
      <p class="empty">Zatiaľ tu nie sú žiadne aktuality.</p>
      <?php endif; ?>
      <div style="margin-top:2.4rem;"><a class="btn btn-ghost" href="aktuality.php">Všetky aktuality →</a></div>
    </div>
  </section>

  <!-- GALÉRIA TEASER -->
  <section>
    <div class="container">
      <div class="head-block reveal">
        <span class="eyebrow">Galéria</span>
        <h2 class="section-title">Momentky z tréningov</h2>
      </div>
      <?php if ($teaserPhotos): ?>
      <div class="gallery">
        <?php foreach ($teaserPhotos as $ph) echo render_gallery_figure($ph, $teaserAlbum['title']); ?>
      </div>
      <?php else: ?>
      <p class="empty">Galéria sa pripravuje.</p>
      <?php endif; ?>
      <div style="margin-top:2.4rem;"><a class="btn btn-ghost" href="galeria.php">Celá galéria →</a></div>
    </div>
  </section>

  <!-- CTA -->
  <section class="cta-band">
    <span class="kr-huge" aria-hidden="true">태권도</span>
    <div class="container cta-inner reveal">
      <h2>Príď si to vyskúšať</h2>
      <p>Prvá tréningová hodina je zdarma a nezáväzne. Stačí vyplniť prihlášku a ozveme sa ti.</p>
      <a class="btn btn-primary" href="kontakty.html#prihlaska">Prihlásiť sa na skúšobnú hodinu →</a>
    </div>
  </section>

<?php require __DIR__ . '/inc/layout-footer.php';
