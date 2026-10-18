<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$page = ['title' => 'Kontakt & prihláška', 'description' => 'Kontakt na klub ALFA Taekwondo Bratislava a prihláška na skúšobnú hodinu zdarma. ZŠ Holíčska 50, tel. 0911 818 815.'];
require __DIR__ . '/inc/layout-header.php';
?>

  <section class="page-hero">
    <span class="kr-bg" aria-hidden="true">연락</span>
    <div class="container">
      <div class="breadcrumb"><a href="index.php">Domov</a> / Kontakt</div>
      <span class="eyebrow">Ozvi sa nám</span>
      <h1>Kontakt</h1>
      <p>Máš otázku alebo sa chceš prísť pozrieť na tréning? Zavolaj, napíš alebo vyplň prihlášku na skúšobnú hodinu.</p>
    </div>
  </section>

  <!-- Kontaktné údaje + kontaktný formulár -->
  <section>
    <div class="container form-wrap">
      <div class="reveal">
        <span class="eyebrow">Kde nás nájdeš</span>
        <h2 class="section-title" style="font-size:clamp(1.8rem,4vw,2.6rem);margin-bottom:1.8rem;">Kontaktné údaje</h2>
        <ul class="contact-list">
          <li><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3 19.5 19.5 0 01-6-6 19.8 19.8 0 01-3-8.7A2 2 0 014.1 2h3a2 2 0 012 1.7c.1.9.3 1.8.6 2.6a2 2 0 01-.5 2.1L8 9.6a16 16 0 006 6l1.2-1.2a2 2 0 012.1-.4c.8.3 1.7.5 2.6.6a2 2 0 011.7 2z"/></svg></span><div><b>Telefón</b><a href="tel:+421911818815">0911 818 815</a></div></li>
          <li><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 6 10 7L22 6"/></svg></span><div><b>E-mail</b><a href="mailto:lub.zavodny1@gmail.com">lub.zavodny1@gmail.com</a></div></li>
          <li><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.3-7-11a7 7 0 0114 0c0 4.7-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg></span><div><b>Kde trénujeme</b><span>ZŠ Holíčska 50, 851 05 Bratislava — Petržalka</span></div></li>
          <li><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/></svg></span><div><b>Tréningy</b><span>Utorok a Štvrtok · 16:30, 17:30 alebo 18:30 (tréning trvá 1 hodinu podľa skupiny)</span></div></li>
        </ul>
        <a class="btn btn-ghost map-link" href="https://www.google.com/maps/search/?api=1&query=Z%C5%A0+Hol%C3%AD%C4%8Dska+50+Bratislava" target="_blank" rel="noopener">Otvoriť v mapách →</a>
      </div>

      <div class="form-card reveal">
        <h3>Napíš nám</h3>
        <p>Odpovieme ti čo najskôr.</p>
        <form data-demo method="post" action="mailto:<?= e(CLUB['email']) ?>?subject=Spr%C3%A1va%20z%20webu" enctype="text/plain">
          <div class="field"><label for="c-meno">Meno a priezvisko</label><input id="c-meno" name="meno" type="text" required></div>
          <div class="field"><label for="c-email">E-mail</label><input id="c-email" name="email" type="email" required></div>
          <div class="field"><label for="c-sprava">Správa</label><textarea id="c-sprava" name="sprava" required></textarea></div>
          <label class="checkbox"><input type="checkbox" required> Beriem na vedomie, ako klub spracúva moje údaje (len na odpoveď na správu). <a href="ochrana-osobnych-udajov.php" style="color:var(--gold-2)">Ochrana osobných údajov</a></label>
          <button class="btn btn-primary" type="submit">Odoslať správu</button>
        </form>
      </div>
    </div>
  </section>

  <!-- Prihláška na skúšobnú hodinu -->
  <section id="prihlaska" style="background:var(--ink-2);border-block:1px solid var(--line);">
    <div class="container">
      <div class="head-block reveal">
        <span class="eyebrow">Zdarma a nezáväzne</span>
        <h2 class="section-title">Prihláška na skúšobnú hodinu <span class="kr">체험</span></h2>
        <p class="lead">Vyplň krátku prihlášku a my sa ti ozveme s termínom prvej tréningovej hodiny — úplne zadarmo.</p>
      </div>
      <div class="form-card reveal" style="max-width:720px;">
        <form data-demo="prihlaska" method="post" action="mailto:<?= e(CLUB['email']) ?>?subject=Prihl%C3%A1%C5%A1ka%20na%20sk%C3%BA%C5%A1obn%C3%BA%20hodinu" enctype="text/plain">
          <div class="field"><label for="p-meno">Meno a priezvisko cvičenca</label><input id="p-meno" name="meno" type="text" required></div>
          <div class="field row2">
            <div><label for="p-prekoho">Prihlasujem</label>
              <select id="p-prekoho" name="prekoho" required>
                <option value="">— vyber —</option>
                <option>Dieťa</option>
                <option>Dospelý (seba)</option>
              </select>
            </div>
            <div><label for="p-den">Preferovaný deň</label>
              <select id="p-den" name="den">
                <option value="">Bez preferencie</option>
                <option>Utorok</option>
                <option>Štvrtok</option>
              </select>
            </div>
          </div>
          <div class="field row2">
            <div><label for="p-tel">Telefón</label><input id="p-tel" name="telefon" type="tel" required></div>
            <div><label for="p-email">E-mail</label><input id="p-email" name="email" type="email" required></div>
          </div>
          <div class="field"><label for="p-sprava">Poznámka (nepovinné)</label><textarea id="p-sprava" name="sprava" placeholder="Napr. vek dieťaťa, doterajšie skúsenosti…"></textarea></div>
          <label class="checkbox"><input type="checkbox" required> Beriem na vedomie, ako klub spracúva údaje z prihlášky. Pri deťoch prihlášku podáva rodič/zákonný zástupca. <a href="ochrana-osobnych-udajov.php" style="color:var(--gold-2)">Ochrana osobných údajov</a></label>
          <button class="btn btn-primary" type="submit">Prihlásiť sa na skúšobnú hodinu →</button>
          <p class="form-note" id="gdpr">Údaje použijeme iba na kontaktovanie ohľadom tréningu a neposkytujeme ich ďalej. Podrobnosti: <a href="ochrana-osobnych-udajov.php" style="color:var(--gold-2)">ochrana osobných údajov</a>.</p>
        </form>
      </div>
    </div>
  </section>

<?php require __DIR__ . '/inc/layout-footer.php';
