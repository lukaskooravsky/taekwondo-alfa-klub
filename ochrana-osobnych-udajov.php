<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$page = ['title' => 'Ochrana osobných údajov', 'description' => 'Ako ' . SITE_NAME . ' spracúva osobné údaje z kontaktného formulára a prihlášky na tréning (GDPR).'];
require __DIR__ . '/inc/layout-header.php';
$c = CLUB;
?>

  <section class="page-hero">
    <div class="container">
      <div class="breadcrumb"><a href="index.php">Domov</a> / Ochrana osobných údajov</div>
      <span class="eyebrow">GDPR</span>
      <h1 class="h1-post">Ochrana osobných údajov</h1>
      <p>Informácie podľa čl. 13 nariadenia (EÚ) 2016/679 (GDPR).</p>
    </div>
  </section>

  <section>
    <div class="container article">
      <div class="prose rich">
        <h2>Kto údaje spracúva</h2>
        <p><?= e(SITE_NAME) ?>, tréner <?= e($c['coach']) ?>, <?= e($c['place']) ?>, <?= e($c['city']) ?>.
          Kontakt: <a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a>, <a href="tel:<?= e($c['phone']) ?>"><?= e($c['phone_display']) ?></a>.</p>

        <h2>Aké údaje, na čo a prečo</h2>
        <ul>
          <li><strong>Kontaktný formulár</strong> — meno, e-mail, prípadne telefón a text správy. Použijeme ich len na odpoveď.
            Právny základ: oprávnený záujem odpovedať na vašu otázku (čl. 6 ods. 1 písm. f).</li>
          <li><strong>Prihláška na skúšobnú hodinu</strong> — meno a vek účastníka, kontakt na rodiča/zákonného zástupcu alebo dospelého
            účastníka, skupina a poznámka. Použijeme ich na dohodnutie skúšobnej hodiny a zaradenie do skupiny.
            Právny základ: kroky pred uzavretím členstva na vašu žiadosť (čl. 6 ods. 1 písm. b). Za dieťa prihlášku podáva rodič
            alebo zákonný zástupca.</li>
        </ul>

        <h2>Ako sa údaje k nám dostanú a kto ich vidí</h2>
        <p>Formulár na webe nič neukladá — otvorí váš e-mailový program s pripravenou správou a vy ju odošlete na
          <?= e($c['email']) ?>. Správu teda uchováva poskytovateľ e-mailu trénera (Google — Gmail) a váš poskytovateľ e-mailu.
          Údaje neposkytujeme ďalším osobám, nepredávame ich a nepoužívame na reklamu.</p>
        <p>Web beží u poskytovateľa webhostingu, ktorý technicky spracúva bežné záznamy o prístupe (IP adresa, čas), aby web fungoval a bol chránený.</p>

        <h2>Ako dlho</h2>
        <p>Správy a prihlášky uchovávame najviac 12 mesiacov od poslednej komunikácie, ak sa nestanete členom klubu.
          Údaje členov uchovávame počas členstva a potom len v rozsahu, ktorý vyžaduje zákon.</p>

        <h2>Cookies a meranie návštevnosti</h2>
        <p>Web nepoužíva reklamné ani analytické cookies a nemeria návštevnosť. Písma sú uložené priamo na webe (bez služieb Google).
          Ak je v článku video z YouTube, načítava sa v režime so zvýšenou ochranou súkromia (youtube-nocookie.com);
          YouTube môže ukladať údaje až po spustení videa.</p>

        <h2>Vaše práva</h2>
        <p>Máte právo na prístup k údajom, ich opravu, vymazanie, obmedzenie spracúvania, prenosnosť a právo namietať.
          Stačí napísať na <a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a>.
          Ak si myslíte, že s údajmi zaobchádzame nesprávne, môžete podať sťažnosť na Úrad na ochranu osobných údajov SR,
          Hraničná 12, 820 07 Bratislava, <a href="https://dataprotection.gov.sk" target="_blank" rel="noopener">dataprotection.gov.sk</a>.</p>
      </div>
    </div>
  </section>

<?php require __DIR__ . '/inc/layout-footer.php';
