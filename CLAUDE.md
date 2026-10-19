# ALFA Taekwondo Club Bratislava — web

Web pre klub **ALFA Taekwondo Club Bratislava** (ITF Taekwon-Do): verejné stránky + administrácia aktualít a galérie pre trénera.

## 🌐 Kde beží
- **Dočasná ukážka (plne funkčná):** https://alfa.oravsky.sk — Lukášov Webglobe hosting, FTP `public_html/_sub/alfa/`,
  PHP 8.4. `SITE_INDEX = false` (neindexuje sa). Admin: `/admin/`.
- **Ostrý web:** nasadí sa na trénerov hosting/doménu podľa `NASADENIE.md` časť 10 (Brian alebo Lukáš).
- Stará statická verzia: GitHub Pages z vetvy `main` (https://lukaskooravsky.github.io/taekwondo-alfa-klub/) — po ostrom spustení vypnúť.
- Repozitár `lukaskooravsky/taekwondo-alfa-klub`, vývoj na vetve **`cms-admin`**.

## Technológie
- **PHP 8.1+ bez frameworku, bez databázy, bez buildu** + CSS + vanilla JS. Všetky stránky sú PHP so spoločným
  layoutom `inc/layout-header.php` / `inc/layout-footer.php` (menu, kontakty v pätičke, SEO) — **meníš na jednom mieste**.
  Staré `*.html` adresy presmeruje `.htaccess` (301).
- Dáta: `data/*.json` (aktuality, albumy, heslo, zálohy v `data/backup/`, relácie v `data/sessions/`), fotky `uploads/`
  (`posts/`, `albums/a_*/`, `inline/` = fotky v texte); kôš zmazaných fotiek `data/trash/` (30 dní). **Nie sú v gite.**
- Nastavenia na jednom mieste: `inc/site.php` — `SITE_URL`, `SITE_INDEX`, `SITE_DEMO`, `CLUB` (fakty o klube pre SEO/AI).
- Editor textu: **Quill 2.0.3** (`assets/vendor/quill/`, self-hosted). Server čistí HTML v `inc/rich.php` (whitelist značiek,
  písiem, farieb, fotky len z `uploads/inline`, video len YouTube → youtube-nocookie). **Palety a písma v `inc/rich.php`
  musia sedieť s `admin/admin.js`.**
- SEO/AI: `inc/seo.php` (canonical, Open Graph, schema.org SportsClub/NewsArticle), `robots.php`, `sitemap.php`,
  `llms.php`, `rss.php` (cez rewrite v `.htaccess` ako `/robots.txt`, `/sitemap.xml`, `/llms.txt`).
- **Koncepty:** aktuality aj albumy majú `status` (`draft` / `published`, chýbajúci = zverejnené). Verejne len
  `posts_public()` / `albums_public()`; koncept vidí cez priamu adresu len prihlásený tréner (`admin_viewer()`, pásik + noindex).
  Tlačidlá: prvé bez `name` = ponechať stav (Enter nikdy nezverejní), `name=publish` / `name=draft`. Nový album = koncept.
- **Nahrávanie fotiek: base64 v TEXTOVÝCH poliach formulára (FormData), nie súbor ani JSON:** Webglobe má `upload_tmp_dir`
  nezapisovateľný (UPLOAD_ERR_NO_TMP_DIR, `.user.ini` to nezmení). PHP drží v pamäti len ~16 kB tela JSON/urlencoded —
  väčšie odkladá do dočasného súboru, a keď nejde, telo zahodí („POST data can't be buffered“ → prázdny vstup). Textové polia
  multipart číta priebežne bez dočasného súboru. Prehliadač fotku zmenší (WebP/JPEG, 1600 px) a pošle do `admin/api/*`
  (`upload_from_api`); súbory idú cez vlastný `data/tmp`. Titulná fotka aktuality sa nahrá vopred (`api/post-image.php`)
  a formulár pošle len `image_ref`. **Formuláre s dlhým textom musia byť `enctype="multipart/form-data"`.**
  Overenie: `php -d upload_tmp_dir=Z:/nope -d sys_temp_dir=Z:/nope -S …` (TMP/TEMP tiež na neexistujúci priečinok).
- Bezpečnosť: bcrypt, rate-limit (5 pokusov/IP, globálny strop 50 — dôveryhodné zariadenie ho obíde), CSRF (prihlásenie
  cez cookie, inde cez reláciu), vlastný session handler (`AlfaSessionHandler` — Webglobe vnucuje nezapisovateľný
  `session.save_path`), bezpečnostné hlavičky `send_security_headers()`, HTTPS pre admin.
- CSS/JS sa načítavajú cez `asset()` s `?v=<čas zmeny>` (cache bez starých verzií).
- Fonty **self-hostované** v `assets/fonts/` (`css/fonts.css`) vrátane `twemoji-country-flags.woff2` (vlajky ako emoji na Windows).
- Lokálne: `php -S localhost:8000` (ignoruje `.htaccess`), testy: `php tests/run.php [filter]`. PHP 8.3 je v
  `C:\Users\lukas\tools\php83` (v neinteraktívnom shelli `export PATH="/c/Users/lukas/tools/php83:$PATH"`).
- Admin z mobilu cez wifi: `ALFA_DEV_LAN=1 php -S 0.0.0.0:8001` → `http://<IP-PC>:8001/admin/`.
- Heslo: aspoň 8 znakov, veľké + malé písmeno, číslica, špeciálny znak, bez časti e-mailu pred @.
- Návrh pôvodného adminu: `docs/superpowers/specs/2026-10-12-cms-admin-design.md`.

## Štruktúra
```
index.php, co-je-taekwondo.php, pasy.php, o-nas.php, kontakty.php, ochrana-osobnych-udajov.php
aktuality.php (12/strana), aktualita.php?id=…, galeria.php (?album=…)
robots.php, sitemap.php, llms.php, rss.php     SEO a AI (generované)
inc/            site.php (nastavenia), bootstrap.php, layout-*, render.php, seo.php, rich.php, posts.php,
                albums.php, upload.php, auth.php, store.php, text.php, admin-layout.php, install.php
admin/          prihlásenie, aktuality (filter/hľadanie), galéria, Nastavenia (e-mail/heslo),
                api/upload.php (fotky albumu), api/inline-image.php (fotky v texte), api/preview.php (náhľad)
css/style.css   hlavný štýl (premenné hore), css/fonts.css
js/main.js      menu, scroll-reveal, lightbox (klávesnica, šípky), formuláre → mailto
assets/         obrázky, fonty, vendor/quill
seed/           ukážkový obsah pre inštaláciu
tests/run.php   testy (php tests/run.php [filter])
tools/          new-install-token.php; e2e/ = testy v prehliadači (Chrome cez DevTools, viď tools/e2e/README.md)
NAVOD-PRE-TRENERA.md  návod na administráciu pre trénera
```

## Dizajn (premenné v `css/style.css`)
- Farby: čierna `#0a0a0c`, zlatá `#c8a04e` / `#e6bd6a`, kosť/biela `#f3efe6`; text `--muted` `#c3bdb1`
  (zosvetlené kvôli čitateľnosti na mobile), `--muted-2` `#948e83`. Pri zmene farby textu drž kontrast na čiernom.
- Fonty: **Saira Condensed** (nadpisy), **Barlow** (text), **Noto Sans KR** (kórejské akcenty 태권도).
- Uhlové rezy cez `--cut` (clip-path). Mobil: hero má fotku na pozadí (`.hero-bg`, ≤820px).

> ⚠️ **Kórejské znaky:** fonty sú *subsety* (태권도, 무도, 太, …). Nový KR znak sa zobrazí ako □ — nepoužívaj ich
> v nových sekciách, alebo regeneruj subset cez `pyftsubset`.

## Ako upraviť obsah
- **Aktuality a galéria**: tréner v `/admin/` (formátovaný text, fotky aj pretiahnutím, YouTube, emoji, náhľad).
- **Texty stránok**: príslušný `*.php` v koreni. **Menu a pätička**: `inc/layout-header.php` / `inc/layout-footer.php`.
- **Fakty o klube** (adresa, tréningy, kontakt pre Google/AI): `inc/site.php` `CLUB` — a zároveň texty stránok.
- **Rozvrh**: tabuľka `.schedule` v `o-nas.php` a `index.php`.
- **Fotky stránok**: `assets/` (hlavná akčná fotka `assets/extrakt.jpg`, 966×760). Disciplíny (v `co-je-taekwondo.php`): `assets/disc-*.jpg`
  (inline `style="background-image:url('assets/…')"` v `co-je-taekwondo.php`).
- **Mapa na Kontakte**: vložená Google mapa (iframe v `kontakty.php`, spomenutá v `ochrana-osobnych-udajov.php` — pri zmene drž oboje).
- Po zmene: `php tests/run.php` **a** pri zmene adminu aj `node tools/e2e/e2e.mjs` (kliky v prehliadači), commit + push,
  nahrať zmenené súbory cez FTP (`NASADENIE.md` časť 8). Pri zmene ovládania aktualizuj `NAVOD-PRE-TRENERA.md`.
- **Pozor:** Quill má `.ql-container`/`.ql-editor` `height:100%` — v grid formulári to editor natiahne cez tlačidlá
  (neviditeľne ich prekryje). V `admin.css` je `height:auto`; po zmenách CSS over `tools/e2e/probe-buttons.mjs`.
- Emoji v editore: len také, ktoré zobrazí aj Windows 10 (Unicode ≤ 12; napr. 🥷 🫡 🫶 sú štvorčeky).

## Čo ešte treba doriešiť (pred ostrým spustením)
- [ ] Reálne tréningové fotky, **logo v priehľadnom PNG**; zmazať ukážkové aktuality/album v admine.
- [ ] Skontrolovať texty (Čo je taekwondo, Pásy, O nás) a stránku Ochrana osobných údajov (doplniť IČO/sídlo klubu, ak existuje).
- [ ] Ostrá doména + hosting → `NASADENIE.md` časť 10 (`SITE_URL`, `SITE_INDEX = true`, Search Console, Firemný profil Google).
- [ ] Vypnúť GitHub Pages, zlúčiť `cms-admin` do `main`.
- [x] Admin (aktuality, galéria, editor, filter, koncepty), SEO/AI, GDPR stránka, bezpečnosť — hotové (2026-10-18/19),
      6 kôl nezávislej kritickej revízie (posledné 0/0/0/3, opravené) + end-to-end test naživo (22/22).

## Fakty o klube (aktuálne v obsahu a v `inc/site.php`)
- Tréner: **Ľubomír Závodný**, IV. Dan čierny pás, Majster Európy.
- Miesto: **ZŠ Holíčska 50, Bratislava — Petržalka**.
- Tréningy: **Utorok a Štvrtok, 16:30–19:30** — začiatočníci 16:30–17:30, stredne pokročilí 17:30–18:30, pokročilí a dospelí 18:30–19:30.
- Kontakt: 0911 818 815, lub.zavodny1@gmail.com.

## Git a nasadenie
- `gh` je prihlásené ako **lukaskooravsky** → bežný `git push origin cms-admin`. Tokeny nedávaj do URL ani `.git/config`.
- FTP Webglobe: `curl --ssl-reqd --resolve ftp.nameserver.sk:21:195.181.251.39 -u '<login>:<heslo>' -T súbor
  ftp://ftp.nameserver.sk/public_html/_sub/alfa/<cesta>` (certifikát FTP je na `*.nameserver.sk`, preto `--resolve`).
  Heslo nikdy neukladaj do repa.
