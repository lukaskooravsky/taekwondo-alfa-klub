# CMS admin pre aktuality a galériu — návrh

- **Dátum:** 2026-10-12
- **Stav:** návrh na schválenie
- **Vetva:** `cms-admin` (`main` / GitHub Pages ostávajú nedotknuté až do nasadenia)

## 1. Cieľ a kontext

Tréner klubu ALFA chce sám pridávať **aktuality** a **fotky do albumov v galérii** bez úprav HTML
a bez pomoci programátora — z mobilu aj z počítača.

**Úspech =** tréner pridá aktualitu s fotkou alebo nový album s fotkami a o chvíľu je to na webe;
dizajn webu ostane nezmenený.

**Zadanie (potvrdené používateľom):**
- Do adminu sa prihlasuje **len tréner** (prípadne Lukáš ako záloha) — jeden účet, jedno heslo.
- Spravuje sa **len obsah aktualít a galérie**. Rozvrh, kontakty a texty stránok ostávajú v kóde.
- Obsah sa pridáva **z mobilu aj z počítača**.
- Web sa presunie na **webhosting Websupport** (websupport.sk, zvolené 2026-10-14) a vlastnú doménu.

**Predpoklady:**
- **Websupport** (podľa ich technickej špecifikácie): Apache + nginx pred ním, PHP 8.2–8.5 v štandarde
  (8.1 a staršie len za príplatok „rozšírená podpora PHP“) → nastaví sa **PHP 8.3 alebo novšie** vo WebAdmine.
  Limity: `memory_limit` 512 MB, `upload_max_filesize` 256 MB, `post_max_size` 512 MB. Let's Encrypt
  certifikát a presmerovanie HTTP → HTTPS automaticky. Prístup cez (S)FTP; **SSH len dočasne (1 h)**,
  preto nič pri nasadení nesmie vyžadovať príkazový riadok na serveri. Denné zálohy robí hosting.
- Kód sám vyžaduje PHP ≥ 8.1, Apache s `.htaccess` (mod_rewrite) a `fileinfo`; `gd` sa overí
  pri inštalácii (`install.php` vypíše chýbajúce rozšírenia).
- Databáza sa nepoužíva.

**Mimo rozsahu:** viac používateľov a rolí, editor s formátovaním (tučné písmo, farby),
presúvanie poradia fotiek, komentáre, správa ostatných stránok, newsletter.

## 2. Zvolený prístup

**Vlastný malý PHP admin s plochými JSON súbormi** (bez databázy a bez frameworku, bez Composeru).

Zamietnuté alternatívy:
- *Hotový CMS (Bludit, WonderCMS)* — web by sa musel prerobiť do ich šablón, admin je anglický a generický.
- *Sveltia/Decap CMS + GitHub + FTP deploy* — tréner by potreboval GitHub účet, zmeny by sa prejavili
  až po builde, nevyužíva PHP hosting.
- *Sveltia na GitHub Pages* — bolo by to odporúčanie pre statický hosting; s PHP hostingom stratilo zmysel.

## 3. Architektúra

### 3.1 Štruktúra súborov

```
index.php                 Domov (3 najnovšie aktuality + 4 fotky z najnovšieho albumu)
aktuality.php             všetky aktuality (najnovšie prvé)
aktualita.php?id=…        detail aktuality (NOVÉ)
galeria.php               zoznam albumov
galeria.php?album=…       fotky jedného albumu (lightbox ako doteraz)
co-je-taekwondo.html, pasy.html, o-nas.html, kontakty.html   ostávajú .html (len upravené odkazy)

inc/
  bootstrap.php           cesty, ensure_storage, načítanie modulov
  render.php, install.php vykresľovanie kariet/404, inštalácia (token, seed, požiadavky)
  layout-header.php       <head>, hlavička a menu (zdieľané PHP stránkami)
  layout-footer.php       pätička
  store.php               čítanie/zápis JSON (atomicky, so zálohou)
  posts.php               logika aktualít
  albums.php              logika albumov a fotiek
  auth.php                prihlásenie, session, CSRF, rate-limit
  upload.php              validácia a uloženie nahraných obrázkov
  text.php                escapovanie, odseky z textu, klikateľné odkazy, formát dátumu

admin/
  index.php               rozcestník (Aktuality / Galéria)
  login.php, logout.php
  posts.php, post-edit.php
  albums.php, album.php
  password.php            zmena hesla
  api/upload.php          príjem jednej fotky (volá sa z JS po jednej)
  admin.css, admin.js     štýl a zmenšovanie fotiek v prehliadači

data/          (mimo gitu)  posts.json, albums.json, auth.json (hash hesla), login-attempts.json,
                            backup/, sessions/ — ak existuje zapisovateľný ../alfa-data (mimo koreňa webu),
                            dáta sa ukladajú tam (záloha pre prípad, že .htaccess neochráni data/)
uploads/       (mimo gitu)  posts/, albums/<album-id>/
install.php    jednorazové nastavenie prvého hesla — beží len s jednorazovým tokenom (viď §5)
install-token.php (mimo gitu) jednorazový token pre install.php; vytvorí ho tools/new-install-token.php
tools/new-install-token.php  CLI skript — spúšťa sa **lokálne na počítači**, vytvorí install-token.php
                          a vypíše odkaz; súbor sa potom nahrá na hosting cez SFTP (na Websupporte
                          nie je trvalé SSH)
inc/site.php   nastavenia webu v kóde (napr. SITE_DEMO — zobrazenie pásika „demo“)
tests/run.php  jednoduchý test runner bez závislostí
seed/          ukážkové dáta a obrázky (súčasný demo obsah) na prvé spustenie
```

`.gitignore` doplní `data/`, `uploads/`, `install-token.php`.

Hash hesla je v `data/auth.json` (nie v PHP súbore), aby zmenu hesla nikdy neskryla opcache
a aby bol chránený rovnako ako ostatné dáta.

### 3.2 Dátový model

`data/posts.json`
```json
{ "posts": [ {
  "id": "p_8f3a2c",
  "title": "Nová sezóna sa začína",
  "date": "2026-09-12",
  "image": { "full": "uploads/posts/p_8f3a2c.webp", "thumb": "uploads/posts/p_8f3a2c_t.webp" },
  "excerpt": "Otvárame nový tréningový rok…",
  "body": "Celý text.\n\nĎalší odsek s odkazom https://…",
  "created": "2026-09-12T18:20:00+02:00",
  "updated": "2026-09-12T18:20:00+02:00"
} ] }
```
- `image` je voliteľný (aktualita môže byť bez fotky — zobrazí sa logo ako zástupný obrázok).
- Zoradenie: podľa `date` zostupne, pri zhode podľa `created`.

`data/albums.json`
```json
{ "albums": [ {
  "id": "a_3k9d1x",
  "title": "Skúšky 2026",
  "date": "2026-06-20",
  "cover": "f_77ab12",
  "photos": [ { "id": "f_77ab12",
                "full": "uploads/albums/a_3k9d1x/f_77ab12.webp",
                "thumb": "uploads/albums/a_3k9d1x/f_77ab12_t.webp",
                "w": 1600, "h": 1067, "added": "2026-06-20T19:02:11+02:00" } ],
  "created": "…", "updated": "…"
} ] }
```
- ID sú náhodné (`random_bytes`), nie odvodené z názvu — premenovanie albumu nemení URL ani cesty.
- `cover` chýba → titulná je prvá fotka; prázdny album sa na webe nezobrazuje.
- Albumy zoradené podľa `date` zostupne, fotky podľa `added`.

### 3.3 Verejné stránky

- PHP vypisuje **tie isté HTML štruktúry** ako dnes (`.posts > article.post`, `.gallery > figure`),
  takže `css/style.css` a lightbox v `js/main.js` fungujú bez zmeny dizajnu.
- „Čítať viac →“ sa stane odkazom na `aktualita.php?id=…`.
- Zoznam albumov: nová malá komponenta `.albums` (dlaždica s titulnou fotkou, názvom, dátumom a počtom
  fotiek) v existujúcom vizuálnom štýle (čierna/zlatá, uhlové rezy `--cut`).
- Obrázky v mriežkach používajú `thumb`, lightbox otvára `full` (cez `data-full`); `loading="lazy"`.
- Prázdne stavy: „Zatiaľ tu nie sú žiadne aktuality.“ / „Galéria sa pripravuje.“
- Neexistujúca aktualita/album → HTTP 404 so stránkou v dizajne webu a odkazom späť.
- Odkazy v menu a pätičke všetkých stránok (aj `.html`) smerujú na `.php` verzie.
- `.htaccess`: 301 presmerovanie `aktuality.html → aktuality.php`, `galeria.html → galeria.php`,
  `index.html → ./`; staré `.html` súbory sa zmažú.

- Pásik „Náhľad webu (demo)…“ sa na PHP stránkach zobrazuje len pri `SITE_DEMO = true` (`inc/site.php`);
  pred ostrým spustením sa vypne (a odstráni zo statických `.html`).
- `aktuality.php` ponechá CTA pás „Chceš byť pri tom?“ ako dnešná `aktuality.html`.

## 4. Admin

### 4.1 Prihlásenie a relácia
- `admin/login.php` — jedno pole „Heslo“. Overenie `password_verify` voči hashu v `data/auth.json`.
- Admin a `install.php` bežia **len cez HTTPS** (presmerovanie v `.htaccess` aj v PHP; za proxy sa
  rešpektuje `X-Forwarded-Proto`). Výnimka: `localhost` pri vývoji.
- Po prihlásení `session_regenerate_id` a nový CSRF token; cookie `HttpOnly`, `SameSite=Lax`, `Secure`
  (na HTTPS), platnosť **30 dní**. Relácie sa ukladajú do `data/sessions/` (handler `files`).
- Relácia si pamätá „verziu hesla“ — **po zmene hesla sa všetky ostatné zariadenia odhlásia**
  (napr. stratený mobil); aktuálne zariadenie ostane prihlásené.
- Rate-limit (`data/login-attempts.json`):
  - **5 chybných pokusov → blokácia 15 minút** pre daného klienta (IPv4 adresa, pri IPv6 celá /64 sieť);
  - adresa klienta = `REMOTE_ADDR`; **len ak je `REMOTE_ADDR` súkromná/loopback adresa** (požiadavka prišla
    cez nginx proxy hostingu), použije sa `X-Real-IP`, inak **posledná** adresa z `X-Forwarded-For` (tú
    pripojila proxy; začiatok zoznamu môže poslať klient). Bez toho by za proxy mali všetci tú istú adresu
    a 5 cudzích pokusov by zamklo aj trénera. Pri verejnej `REMOTE_ADDR` sa hlavičky ignorujú. Či proxy
    hostingu hlavičky naozaj prepisuje, sa overí pri nasadení: `install.php` zobrazí zistenú adresu
    a `NASADENIE.md` obsahuje test s podvrhnutými hlavičkami;
  - IPv4-mapped IPv6 adresy (`::ffff:a.b.c.d`) sa berú ako IPv4;
  - globálny strop **50 chybných pokusov za 15 minút** zo všetkých adries → prihlasovanie sa na 15 minút
    zablokuje pre všetkých (ochrana proti hádaniu z mnohých adries);
  - pokus sa **započíta ešte pred overením hesla** v jednom zamknutom kroku, takže ani súbežné požiadavky
    nedostanú viac pokusov; úspešné prihlásenie počítadlo klienta vynuluje;
  - súbor má najviac 500 záznamov, nezálohuje sa a poškodený sa považuje za prázdny (nikoho nezamkne).
- Každá stránka v `admin/` (okrem login) vyžaduje prihlásenie; inak presmeruje na login.
- `admin/password.php` — zmena hesla (staré heslo + nové 2×, min. 10 znakov).

### 4.2 Aktuality
- Zoznam: náhľad, nadpis, dátum, „Upraviť“, „Zmazať“; hore „+ Nová aktualita“.
- Formulár: **Nadpis** (povinný, max 150 znakov), **Dátum** (predvyplnený dnes), **Fotka** (voliteľná,
  `accept="image/*"` → na mobile ponúkne fotoaparát aj galériu), **Krátky úvod** (povinný, max 300),
  **Celý text** (voliteľný, max 20 000; prázdny riadok = nový odsek, URL sa stanú odkazmi).
- Po výbere fotky sa ukáže jej náhľad a „Fotka pripravená“; výber ostane viditeľný.
- Pri úprave sa dá fotka vymeniť alebo odstrániť.
- Zmazanie: potvrdenie druhým kliknutím („Naozaj? Klikni znova“), zmaže sa aj súbor fotky.
- Ak aktualitu medzitým niekto zmazal (iná karta), uloženie ohlási „Aktualita sa nenašla.“ a nahratú
  fotku zmaže.

### 4.3 Galéria
- Zoznam albumov: titulná fotka, názov, dátum, počet fotiek; „+ Nový album“ (názov, dátum).
- Detail albumu:
  - **„Pridať fotky“** — výber viacerých naraz (aj 30+). JS ich zmenší a posiela **po jednej** na
    `admin/api/upload.php`; ukazuje priebeh „Nahrávam 7 / 30“. Zlyhaná fotka nezastaví ostatné,
    na konci sa vypíše, ktoré zlyhali, s možnosťou skúsiť znova. Počas nahrávania prehliadač
    varuje pred odchodom zo stránky.
  - Mriežka fotiek s „Zmazať“ a „Nastaviť ako titulnú“.
  - Premenovanie / zmena dátumu albumu; zmazanie celého albumu (potvrdenie, zmaže aj priečinok).
- Poradie fotiek = poradie nahratia (bez drag & drop).

### 4.4 Spracovanie fotiek
- V prehliadači (canvas): veľká verzia max **1600 px** na dlhšej strane, náhľad max **480 px**,
  formát **WebP** (kvalita ~0,82), fallback JPEG, ak prehliadač WebP neenkóduje. Rešpektuje sa orientácia
  z EXIF (`createImageBitmap` s `imageOrientation: "from-image"`); EXIF (GPS) sa tým zahodí.
- Na server ide dvojica súborov (full + thumb) v jednom `multipart` požiadavku.
- Ak JS zlyhá alebo nie je k dispozícii, server prijme pôvodný súbor do 5 MB a zmenší ho cez GD
  (ak je na hostingu dostupné), inak ho uloží tak, ako je. Platí pre fotku aktuality; **hromadné
  nahrávanie do albumu vyžaduje JavaScript** (bez neho admin zobrazí hlášku).
- Server pred dekódovaním cez GD odmietne fotku nad **25 megapixelov** („Fotka má príliš vysoké
  rozlíšenie…“) a zvýši `memory_limit` na 256 MB, ak je nižší (nikdy ho neznižuje), aby veľká fotka
  neskončila bielou stránkou.
- Ak je fotka väčšia ako limit hostingu (`upload_max_filesize`, resp. `post_max_size`), hláška uvedie
  skutočný limit servera.

### 4.5 Vzhľad
- Rovnaké farby a fonty ako web (premenné z `css/style.css`), vlastný jednoduchý `admin.css`.
- Mobile-first: tlačidlá a polia min. 44 px výška, jednostĺpcové formuláre, všetko po slovensky.
- Po uložení zelené hlásenie „Uložené.“ s odkazom „Pozrieť na webe“.

## 5. Bezpečnosť

| Hrozba | Opatrenie |
|---|---|
| Hádanie hesla | `password_hash` (bcrypt/argon2); rate-limit 5 / 15 min na klienta (IPv6 po /64), globálny strop 50 / 15 min, pokus sa počíta pred overením (§4.1) |
| Odpočúvanie hesla / cookie | admin a install len cez HTTPS (presmerovanie), cookie `Secure` aj za proxy (`X-Forwarded-Proto`) |
| CSRF | token v session (nový po prihlásení), povinný v každom POST formulári aj v upload API |
| XSS | všetok text z dát sa vypisuje cez `htmlspecialchars`; odkazy sa tvoria až po escapovaní, len `http(s)://` |
| Nahratie skriptu | povolené len JPEG/PNG/WebP (kontrola `finfo` + `getimagesize`), max 5 MB, nové náhodné meno + prípona podľa skutočného typu; koreňový `.htaccess` zakáže `*.php` v `uploads/` |
| Čítanie dát zvonka | koreňový `.htaccess` vráti 403 pre `data/`, `inc/`, `tools/`, `seed/`, `tests/`, `docs/`, `_zaloha/`, `*.md`; navyše `data/.htaccess` (`Require all denied`) |
| Path traversal | ID sa validujú regexom `^[paf]_[a-z0-9]{6,32}$` s modifikátorom `D` (bez koncového `\n`), cesty sa nikdy neskladajú zo vstupu priamo |
| Únik / krádež session | `HttpOnly`, `SameSite=Lax`, `Secure`, regenerácia ID po prihlásení; zmena hesla zneplatní ostatné relácie |
| Únos inštalácie | `install.php` beží len, ak na hostingu existuje `install-token.php` a požiadavka nesie ten istý token; `data/auth.json` sa vytvára výhradne (`fopen` režim `x`), token sa po úspechu zmaže; existujúci `auth.json` → 403 |

## 6. Spoľahlivosť a chyby

- Zápis JSON: `flock` + zápis do dočasného súboru + `rename` (atomicky). Pred zápisom kópia do
  `data/backup/` (posledných 20 verzií na súbor; okrem `login-attempts.json`).
- Poškodený/chýbajúci JSON → web zobrazí prázdny stav (HTTP 200); **každá** stránka adminu aj upload API
  zachytí chybu čítania a ukáže hlášku „Dáta sa nepodarilo načítať — obnov zálohu“ (API vráti JSON), nič
  sa neprepíše. Výnimka: poškodený `login-attempts.json` sa považuje za prázdny.
- Chyby pre trénera po slovensky („Fotka je príliš veľká (max 5 MB).“, „Nepodarilo sa uložiť, skús znova.“);
  technické detaily len do `error_log`.
- Pri mazaní: najprv sa upravia dáta, potom sa zmažú súbory (ak zmazanie súboru zlyhá, ostane len
  nepoužitý súbor, nie rozbitý odkaz).
- `install.php` najprv skontroluje požiadavky hostingu (PHP ≥ 8.1, na Websupporte nastaviť 8.3+; `fileinfo`, `mbstring`, `json`;
  `gd` ako odporúčané) a chýbajúce vypíše po slovensky.

## 7. Testovanie

- **Lokálne:** PHP nainštalované cez `winget`, spustenie `php -S localhost:8000`.
- **Automatické testy** (`php tests/run.php`, bez závislostí):
  store (atomický zápis, záloha, poškodený JSON, „volatile“ súbory), posts/albums (CRUD, zoradenie,
  titulná fotka, mazanie súborov), upload (odmietnutie ne-obrázka, prípony, veľkosti, limit megapixelov),
  auth (správne/zlé heslo, rate-limit vrátane globálneho stropu a IPv6 /64, CSRF, zneplatnenie relácií
  po zmene hesla), install (token, výhradné vytvorenie `auth.json`, seed), text, render.
- **Ručne v prehliadači** (desktop + mobilné zobrazenie): celý tok pridania/úpravy/zmazania aktuality,
  nahratie 10+ fotiek do albumu, titulná fotka, zmazanie albumu, prihlásenie/odhlásenie, zablokovanie.

## 8. Migrácia a nasadenie

- Súčasný ukážkový obsah (6 aktualít, obrázky galérie) sa prevedie do `seed/`. `install.php` ho pri
  prvom spustení (voliteľne) skopíruje do `data/` a `uploads/`, aby web nebol prázdny.
- **Poradie nasadenia:** na hosting sa nahrávajú súbory z vetvy `cms-admin` (ZIP z GitHubu). Do `main`
  sa vetva zlúči až **po** vypnutí GitHub Pages alebo jeho nahradení presmerovaním na novú doménu —
  inak by Pages (bez PHP) ostal bez `index.html`.
- `NASADENIE.md` písaný **pre Websupport** (WebAdmin: výber PHP verzie, SSL, SFTP prístup, kde je
  koreň webu): požiadavky (PHP 8.3+, `fileinfo`, `mbstring`, `gd`, `.htaccess`, HTTPS),
  vygenerovanie `install-token.php` lokálne a nahratie cez SFTP, kontrola zistenej IP adresy v `install.php`,
  odkiaľ brať súbory, nastavenie hesla, kontroly po
  nasadení (403 na `data/`, fotky sa načítajú, HTTPS presmerovanie), čo robiť pri chybe 500 v `.htaccess`,
  zálohovanie `data/` + `uploads/`, zabudnuté heslo, nasmerovanie domény.
- `CLAUDE.md` a `README.md` sa aktualizujú (nová štruktúra, ako spravovať obsah, ako nasadzovať).
