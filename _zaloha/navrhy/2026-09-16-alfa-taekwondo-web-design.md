# ALFA Taekwondo Club Bratislava — návrh webu

**Dátum:** 2026-09-16
**Stav:** návrh na schválenie (v2 — po kritickom prehodnotení)

## Cieľ

Úplne nový web pre klub **ALFA Taekwondo Club Bratislava** (ITF Taekwon-Do) a
jeho trénera **Ľubomíra Závodného**. Web pobeží na **vlastnej doméne klienta**
(zatiaľ nekúpená — demo beží na bezplatnej adrese, doména sa napojí neskôr).

## Architektúra (zvolená cesta A)

**Statický web v Astro na modernom hostingu + git-based CMS.** Jeden stack,
žiadny PHP, žiadna údržba servera.

- **Astro** — komponentový layout (spoločná navigácia, pätička, layout),
  statický HTML výstup.
- **Hosting: Netlify** (bezplatný plán) — git-deploy, formuláre a prihlásenie
  do CMS „z krabice". Alternatívy Cloudflare Pages / Vercel.
- **CMS: Decap / Sveltia** (git-backed) — tréner edituje **Aktuality** a
  **Galériu** cez jednoduchý admin bez programovania. Prihlásenie cez GitHub
  OAuth.
- **Formuláre: Netlify Forms** — kontakt aj prihláška chodia na email bez
  cudzej služby.

### Prečo táto cesta (a nie hybrid Astro + PHP)

Klasický shared PHP hosting nemá Node.js → nedá sa tam spustiť `astro build`,
takže tréner by nevedel editovať obsah bez lokálneho buildu a nahrávania.
Miešanie Astro (statické) + PHP (dynamické Aktuality) by znamenalo dva
šablónovacie systémy a duplicitný dizajn. Cesta A tento rozpor odstraňuje:
jeden stack, CMS rieši editovanie, hosting rieši formuláre.

### Fázy

**Fáza 1 — Demo**
- Kompletný statický web na bezplatnej adrese (napr. `*.netlify.app`).
- Funkčné formuláre (Netlify Forms) a funkčný CMS admin.
- Ukážkové Aktuality a fotky (kým tréner nedodá vlastné).

**Fáza 2 — Produkcia**
- Napojenie zakúpenej domény, HTTPS.
- Doplnenie reálnych textov, loga (vektor/PNG) a fotiek od trénera.
- Odovzdanie CMS prístupu trénerovi.

## Technológie

- **Astro** (statický výstup), **HTML/CSS/JS**.
- **Netlify** — hosting, deploy, formuláre.
- **Decap / Sveltia CMS** — správa Aktualít a Galérie (git-backed).
- **GitHub** — repozitár + zdroj pre CMS a deploy.
- **Self-hostované fonty** (kvôli GDPR a rýchlosti).

## Štruktúra webu (7 podstránok)

1. **Domov** — hero (logo, claim *„Bojové umenie na rozvoj sily, kondície a
   obratnosti"*), rýchle info (kde/kedy), **veľké CTA „Príď na skúšobnú
   hodinu"** (odkaz/rozbalenie prihlášky), náhľad Aktualít a Galérie,
   predstavenie trénera.
2. **Čo je taekwondo** — čo je ITF taekwondo, filozofia, čo tréning rozvíja,
   pre koho (deti/dospelí, začiatočníci/pokročilí).
3. **Pásy** — systém pásov ITF (gup/dan stupne), farby pásov a ich význam;
   vizuálna sekcia.
4. **Aktuality** — zoznam príspevkov + detail príspevku (spravuje tréner cez
   CMS).
5. **Galéria** — mriežka fotiek s lightboxom (spravuje tréner cez CMS).
6. **O nás** — o klube + tréner (Ľubomír Závodný, IV. Dan, Majster Európy,
   viacnásobný majster SR), **rozvrh tréningov**, rozlíšenie
   začiatočníci/pokročilí, miesto (ZŠ Holíčska 50, Bratislava).
7. **Kontakty** — telefón, email, adresa, **odkaz na mapu** (nie embed s
   cookies), **kontaktný formulár** + **prihláška na skúšobnú hodinu**.

## Spoločné komponenty

- **Header / navigácia** — sticky, logo, menu na všetky podstránky.
- **Footer** — kontakt, logá ITF, odkaz na mapu, odkaz na Zásady ochrany OÚ.
- **Layout** — obaľuje stránky, načítava self-hostované fonty a štýly.

## Dizajnový systém

Podľa existujúceho loga:
- **Farby:** čierna (pozadie), zlatá/béžová (~#C9A15C, akcenty), biela (text).
- **Písmo nadpisov:** ostré geometrické (v štýle loga — napr. Orbitron /
  Rajdhani, self-hostované). **Text:** čitateľný sans (Inter). Akcenty
  kórejským znakom 태권도.
- **Štýl:** tmavý, „bojový", moderný, vysoký kontrast, decentné animácie.
- **Responzívne** (mobile-first) a **prístupné** (kontrast, alt texty,
  ovládanie klávesnicou).

## Formuláre a GDPR

**Prihláška na skúšobnú hodinu:** meno, pre koho (dieťa/dospelý), telefón,
email, preferovaný deň (Utorok/Štvrtok), správa, **súhlas so spracovaním
osobných údajov** (pri deťoch súhlas rodiča/zákonného zástupcu).

**Kontaktný formulár:** meno, email, správa, súhlas.

- Odoslanie cez **Netlify Forms** na `lub.zavodny1@gmail.com`.
- Validácia na strane klienta + ochrana proti spamu (honeypot).
- **Stránka „Zásady ochrany osobných údajov"** — povinná, keďže zbierame
  osobné údaje vrátane údajov o deťoch.

## Údaje o klube (z podkladov)

- **Klub:** ALFA Taekwondo Club Bratislava (ITF Taekwon-Do Slovakia).
- **Tréner:** Ľubomír Závodný — IV. Dan čierny pás, Majster Európy,
  viacnásobný majster SR.
- **Rozvrh:** Utorok a Štvrtok — 17:00–18:00 Deti · 18:00–19:00
  Pokročilí + Dospelí.
- **Miesto:** ZŠ Holíčska 50, Bratislava.
- **Kontakt:** 0911 818 815, lub.zavodny1@gmail.com.
- **Claim:** „Bojové umenie na rozvoj sily, kondície a obratnosti".

## Obsah a podklady

- Slovenské texty (*Čo je taekwondo*, *Pásy*, *O nás*) napíšem ja ako návrh;
  tréner doladí/schváli.
- **Logo:** tréner dodá vo vektore / PNG s priehľadným pozadím (máme len JPEG
  na čiernom). Do dema dočasne použijem existujúce.
- **Fotky do galérie:** tréner dodá tréningové/súťažné fotky; do dema dám
  existujúce + pár placeholderov.

## Testovanie a nasadenie

- `astro build` prejde bez chýb; kontrola odkazov.
- Responzívna kontrola (mobil/tablet/desktop) a prístupnosť.
- Test reálneho odoslania oboch formulárov (Netlify Forms).
- Test CMS: prihlásenie, pridanie/úprava príspevku a fotky.
- Lighthouse (výkon, prístupnosť, SEO).
- Deploy na Netlify (demo adresa), neskôr napojenie domény.

## Mimo rozsahu (zatiaľ)

- Viacjazyčnosť (web je po slovensky).
- E-shop / platby / online rezervačný/dochádzkový systém.
- Migrácia z existujúceho webu (robíme úplne nový).
