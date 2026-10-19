---
name: project-alfa-taekwondo-web
description: "ALFA Taekwondo Club Bratislava — PHP web s adminom; kde beží (alfa.oravsky.sk ukážka), stav, čo ostáva"
metadata:
  node_type: memory
  type: project
  originSessionId: b3956cdb-161a-4185-8cf7-509308ed727e
  modified: 2026-10-19T18:15:58.634Z
---

Web pre klub **ALFA Taekwondo Club Bratislava** (ITF Taekwon-Do) pre trénera Ľubomíra Závodného. Podrobnosti v `CLAUDE.md` a `NASADENIE.md` repa.

**Stav 2026-10-18:** vetva `cms-admin` (repo lukaskooravsky/taekwondo-alfa-klub). PHP bez DB, všetky stránky na spoločnom layoute
(.html → .php, 301). Admin: aktuality + albumy, editor Quill 2.0.3 (fotky v texte, YouTube nocookie, zarovnanie, citát, čiara,
farby/pozadie, emoji v kategóriách vrátane Taekwondo, vlajky cez Twemoji font, náhľad, celá obrazovka), filter/hľadanie
(bez diakritiky), drag&drop fotiek, Nastavenia v menu. SEO/AI: inc/seo.php, robots/sitemap/llms/rss, schema.org. GDPR stránka.
Bezpečnosť: vlastný session handler, dôveryhodné zariadenie, login CSRF cez cookie, hlavičky, kôš data/trash.
Koncepty aktualít aj albumov (uložiť bez zverejnenia). Fotky sa nahrávajú cez JSON/base64 (viď [[reference-webglobe-php-tmp]]).
Návod pre trénera: `NAVOD-PRE-TRENERA.md`; testy v prehliadači: `tools/e2e/` (e2e.mjs — 22 krokov, overené aj naživo).

**Nasadenie (dočasné):** https://alfa.oravsky.sk — Lukášov Webglobe hosting, len ukážka pre trénera („plne funkčná“),
`SITE_INDEX=false` (aby Google nepovažoval ostrú doménu za duplikát). FTP `public_html/_sub/alfa/`, cert FTP na `*.nameserver.sk`
→ curl `--resolve ftp.nameserver.sk:21:195.181.251.39`. Webglobe vnucuje nezapisovateľný session.save_path (preto AlfaSessionHandler).
Admin nainštalovaný na e-mail lub.zavodny1@gmail.com (heslo poznal používateľ — mal si ho zmeniť; aj FTP heslo).

**Nasadené naživo 2026-10-19** po 6 kolách nezávislej kritickej revízie (0/0/3/5 → 0/0/2/7 → 0/0/2/5 → 0/0/1/2 → 0/0/1/0 → 0/0/0/3, všetko opravené; po 6. kole pribudli koncepty a JSON upload — ďalšie kolo ešte nebolo). Pozor: Quill `.ql-container`/`.ql-editor` majú height:100% — v grid formulári prekrývali tlačidlá (opravené `height:auto`); po zmenách adminu spusti `tools/e2e/`.

**Finálne:** ostrý web na trénerov hosting/doménu (Brian alebo Lukáš) podľa NASADENIE.md časť 10 (SITE_URL, SITE_INDEX=true,
preniesť data/ + uploads/, Search Console, Firemný profil Google, presmerovať alfa.oravsky.sk).

**2026-10-19:** nahrávanie fotiek opravené (base64 v textových poliach formulára, viď [[reference-webglobe-php-tmp]]) —
používateľ overil, že na alfa.oravsky.sk funguje. Titulná fotka aktuality: popisky Aktuálna/Nová, „Zrušiť novú fotku“,
„Odstrániť túto fotku (bez náhrady)“ so zošednutím — nasadené, klikom v prihlásenom admine zatiaľ neoverené (e2e nebežalo).
FTP heslo používateľ napísal do chatu 2026-10-19 — odporučené zmeniť; nové heslo nepýtať do chatu, nech spustí deploy sám v Git Bash.

**Deploy na FTP (od 2026-10-19):** po commite spusti používateľovi okno
`("/c/Users/lukas/AppData/Local/Programs/Git/git-bash.exe" tools/nahraj-ftp.sh &)` z koreňa repa — skript nahrá zmeny
od git značky `ftp-alfa` (FTP meno dusan@oravsky.sk, ftp.oravsky.sk = 195.181.251.39), používateľ len napíše heslo,
značka sa posunie. Heslo z chatu nepoužívať (používateľ ho tam opakovane posiela a chce, aby to Claude urobil sám — vysvetliť krátko a otvoriť okno).
Pozor: Windows curl nečíta `<(...)` ani `--netrc-file` s MSYS cestou → cygpath -w.
**2026-10-19 nasadené:** disciplíny presunuté z Domov na Čo je taekwondo; Kontakt má interaktívnu Google mapu na celú šírku (iframe www.google.com/maps/embed, zmienka v GDPR).

**Ostáva:** reálne fotky + logo PNG, zmazať ukážkový obsah, skontrolovať texty a GDPR (IČO), ostrá doména, vypnúť GitHub Pages, merge do main.

Pozor na fonty: [[fonts-kr-subset-gotcha]]. Git: [[deploy-git-lukasko-token]]. Lokálne PHP: [[reference-local-php]].
Postup práce: [[feedback-ukladaj-priebezne]], [[feedback-review-nulove]].

**Balík pre trénera (2026-10-19):** `php -d extension=zip tools/zabal-pre-trenera.php` → `../ALFA-web-<dátum>.zip` (web/ + návod
NASADENIE-PRE-TRENERA.md s jednorazovým install tokenom + návod na admin). SITE_URL/SITE_INDEX sa zisťujú samy z domény
(SITE_DEMO_HOSTS = alfa.oravsky.sk → noindex), tréner nič v kóde nemení. Rozhodnuté: tréner začína načisto (bez obsahu z ukážky).
Každé spustenie skriptu = nový token (starší ZIP potom stále platí, kým sa nepoužije — token je v jeho install-token.php).
