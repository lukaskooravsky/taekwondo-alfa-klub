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
- Web sa presunie na **klasický webhosting s PHP** a vlastnou doménou (poskytovateľ zatiaľ nevybraný).

**Predpoklady:**
- Hosting má PHP ≥ 8.1, Apache s `.htaccess` (mod_rewrite) a rozšírenie `fileinfo`. To je štandard
  slovenských/českých hostingov; overí sa pri výbere.
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
  bootstrap.php           načítanie configu, nastavenia, autoload funkcií
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

data/          (mimo gitu)  posts.json, albums.json, login-attempts.json, backup/
uploads/       (mimo gitu)  posts/, albums/<album-id>/
config.php     (mimo gitu)  hash hesla, názov webu; vzor v config.sample.php
install.php    jednorazové nastavenie prvého hesla (po použití sa sám zablokuje)
tests/run.php  jednoduchý test runner bez závislostí
seed/          ukážkové dáta a obrázky (súčasný demo obsah) na prvé spustenie
```

`.gitignore` doplní `data/`, `uploads/`, `config.php`.

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

## 4. Admin

### 4.1 Prihlásenie a relácia
- `admin/login.php` — jedno pole „Heslo“. Overenie `password_verify` voči hashu v `config.php`.
- Po prihlásení `session_regenerate_id`, cookie `HttpOnly`, `SameSite=Lax`, `Secure` (na HTTPS),
  platnosť **30 dní**.
- Rate-limit: **5 chybných pokusov → blokácia 15 minút** (podľa IP, uložené v `data/login-attempts.json`).
- Každá stránka v `admin/` (okrem login) vyžaduje prihlásenie; inak presmeruje na login.
- `admin/password.php` — zmena hesla (staré heslo + nové 2×, min. 10 znakov).

### 4.2 Aktuality
- Zoznam: náhľad, nadpis, dátum, „Upraviť“, „Zmazať“; hore „+ Nová aktualita“.
- Formulár: **Nadpis** (povinný, max 150 znakov), **Dátum** (predvyplnený dnes), **Fotka** (voliteľná,
  `accept="image/*"` → na mobile ponúkne fotoaparát aj galériu), **Krátky úvod** (povinný, max 300),
  **Celý text** (voliteľný; prázdny riadok = nový odsek, URL sa stanú odkazmi).
- Pri úprave sa dá fotka vymeniť alebo odstrániť.
- Zmazanie: potvrdenie „Naozaj zmazať?“, zmaže sa aj súbor fotky.

### 4.3 Galéria
- Zoznam albumov: titulná fotka, názov, dátum, počet fotiek; „+ Nový album“ (názov, dátum).
- Detail albumu:
  - **„Pridať fotky“** — výber viacerých naraz (aj 30+). JS ich zmenší a posiela **po jednej** na
    `admin/api/upload.php`; ukazuje priebeh „Nahrávam 7 / 30“. Zlyhaná fotka nezastaví ostatné,
    na konci sa vypíše, ktoré zlyhali, s možnosťou skúsiť znova.
  - Mriežka fotiek s „Zmazať“ a „Nastaviť ako titulnú“.
  - Premenovanie / zmena dátumu albumu; zmazanie celého albumu (potvrdenie, zmaže aj priečinok).
- Poradie fotiek = poradie nahratia (bez drag & drop).

### 4.4 Spracovanie fotiek
- V prehliadači (canvas): veľká verzia max **1600 px** na dlhšej strane, náhľad max **480 px**,
  formát **WebP** (kvalita ~0,82), fallback JPEG, ak prehliadač WebP neenkóduje. Rešpektuje sa orientácia
  z EXIF (`createImageBitmap` s `imageOrientation: "from-image"`); EXIF (GPS) sa tým zahodí.
- Na server ide dvojica súborov (full + thumb) v jednom `multipart` požiadavku.
- Ak JS zlyhá alebo nie je k dispozícii, formulár aktuality funguje aj bez neho — server prijme pôvodný
  súbor do 5 MB a zmenší ho cez GD (ak je na hostingu dostupné), inak ho uloží tak, ako je.

### 4.5 Vzhľad
- Rovnaké farby a fonty ako web (premenné z `css/style.css`), vlastný jednoduchý `admin.css`.
- Mobile-first: tlačidlá a polia min. 44 px výška, jednostĺpcové formuláre, všetko po slovensky.
- Po uložení zelené hlásenie „Uložené.“ s odkazom „Pozrieť na webe“.

## 5. Bezpečnosť

| Hrozba | Opatrenie |
|---|---|
| Hádanie hesla | `password_hash` (bcrypt/argon2), rate-limit 5 / 15 min |
| CSRF | token v session, povinný v každom POST formulári aj v upload API |
| XSS | všetok text z dát sa vypisuje cez `htmlspecialchars`; odkazy sa tvoria až po escapovaní, len `http(s)://` |
| Nahratie skriptu | povolené len JPEG/PNG/WebP (kontrola `finfo` + `getimagesize`), max 5 MB, nové náhodné meno + prípona podľa skutočného typu; v `uploads/.htaccess` zakázané spúšťanie PHP |
| Čítanie dát zvonka | `data/.htaccess`: `Require all denied`; `config.php` nevracia nič pri priamom volaní |
| Path traversal | ID sa validujú regexom (`^[pfa]_[a-z0-9]{6,}$`), cesty sa nikdy neskladajú zo vstupu priamo |
| Únik session | `HttpOnly`, `SameSite=Lax`, `Secure` na HTTPS, regenerácia ID po prihlásení |
| `install.php` po inštalácii | odmietne bežať, ak `config.php` už existuje |

## 6. Spoľahlivosť a chyby

- Zápis JSON: `flock` + zápis do dočasného súboru + `rename` (atomicky). Pred zápisom kópia do
  `data/backup/` (posledných 20 verzií na súbor).
- Poškodený/chýbajúci JSON → web zobrazí prázdny stav, admin hlášku „Dáta sa nepodarilo načítať —
  obnov zálohu“, nič sa neprepíše.
- Chyby pre trénera po slovensky („Fotka je príliš veľká (max 5 MB).“, „Nepodarilo sa uložiť, skús znova.“);
  technické detaily len do `error_log`.
- Pri mazaní: najprv sa upravia dáta, potom sa zmažú súbory (ak zmazanie súboru zlyhá, ostane len
  nepoužitý súbor, nie rozbitý odkaz).

## 7. Testovanie

- **Lokálne:** PHP nainštalované cez `winget`, spustenie `php -S localhost:8000`.
- **Automatické testy** (`php tests/run.php`, bez závislostí):
  store (atomický zápis, záloha, poškodený JSON), posts/albums (CRUD, zoradenie, titulná fotka,
  mazanie súborov), upload (odmietnutie ne-obrázka, prípony, veľkosti), auth (správne/zlé heslo,
  rate-limit, CSRF), text (escapovanie, odseky, odkazy).
- **Ručne v prehliadači** (desktop + mobilné zobrazenie): celý tok pridania/úpravy/zmazania aktuality,
  nahratie 10+ fotiek do albumu, titulná fotka, zmazanie albumu, prihlásenie/odhlásenie, zablokovanie.

## 8. Migrácia a nasadenie

- Súčasný ukážkový obsah (6 aktualít, obrázky galérie) sa prevedie do `seed/`. `install.php` ho pri
  prvom spustení skopíruje do `data/` a `uploads/`, aby web nebol prázdny.
- `NASADENIE.md`: výber hostingu (PHP ≥ 8.1, `.htaccess`, HTTPS), nahratie cez FTP/správcu súborov,
  spustenie `install.php` a nastavenie hesla, zálohovanie `data/` + `uploads/`, nasmerovanie domény.
- GitHub Pages: ostáva na `main` v súčasnej statickej podobe, kým hosting nebude pripravený.
  Po nasadení na hosting sa Pages vypne alebo nahradí presmerovaním na novú doménu.
- `CLAUDE.md` sa aktualizuje (nová štruktúra, ako spravovať obsah, ako nasadzovať).
