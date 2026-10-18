---
name: project-alfa-taekwondo-web
description: "ALFA Taekwondo Club Bratislava — PHP web s adminom; kde beží (alfa.oravsky.sk ukážka), stav, čo ostáva"
metadata:
  node_type: memory
  type: project
  originSessionId: b3956cdb-161a-4185-8cf7-509308ed727e
  modified: 2026-10-18T20:43:49.890Z
---

Web pre klub **ALFA Taekwondo Club Bratislava** (ITF Taekwon-Do) pre trénera Ľubomíra Závodného. Podrobnosti v `CLAUDE.md` a `NASADENIE.md` repa.

**Stav 2026-10-18:** vetva `cms-admin` (repo lukaskooravsky/taekwondo-alfa-klub). PHP bez DB, všetky stránky na spoločnom layoute
(.html → .php, 301). Admin: aktuality + albumy, editor Quill 2.0.3 (fotky v texte, YouTube nocookie, zarovnanie, citát, čiara,
farby/pozadie, emoji v kategóriách vrátane Taekwondo, vlajky cez Twemoji font, náhľad, celá obrazovka), filter/hľadanie
(bez diakritiky), drag&drop fotiek, Nastavenia v menu. SEO/AI: inc/seo.php, robots/sitemap/llms/rss, schema.org. GDPR stránka.
Bezpečnosť: vlastný session handler, dôveryhodné zariadenie, login CSRF cez cookie, hlavičky, kôš uploads/.trash.

**Nasadenie (dočasné):** https://alfa.oravsky.sk — Lukášov Webglobe hosting, len ukážka pre trénera („plne funkčná“),
`SITE_INDEX=false` (aby Google nepovažoval ostrú doménu za duplikát). FTP `public_html/_sub/alfa/`, cert FTP na `*.nameserver.sk`
→ curl `--resolve ftp.nameserver.sk:21:195.181.251.39`. Webglobe vnucuje nezapisovateľný session.save_path (preto AlfaSessionHandler).
Admin nainštalovaný na e-mail lub.zavodny1@gmail.com (heslo poznal používateľ — mal si ho zmeniť; aj FTP heslo).

**Finálne:** ostrý web na trénerov hosting/doménu (Brian alebo Lukáš) podľa NASADENIE.md časť 10 (SITE_URL, SITE_INDEX=true,
preniesť data/ + uploads/, Search Console, Firemný profil Google, presmerovať alfa.oravsky.sk).

**Ostáva:** reálne fotky + logo PNG, zmazať ukážkový obsah, skontrolovať texty a GDPR (IČO), ostrá doména, vypnúť GitHub Pages, merge do main.

Pozor na fonty: [[fonts-kr-subset-gotcha]]. Git: [[deploy-git-lukasko-token]]. Lokálne PHP: [[reference-local-php]].
Postup práce: [[feedback-ukladaj-priebezne]], [[feedback-review-nulove]].
