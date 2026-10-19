# Nasadenie webu ALFA Taekwondo

Web je **PHP bez databázy** (PHP 8.1+, Apache s `.htaccess`). Obsah od trénera (aktuality, albumy, heslo) je v `data/`,
fotky v `uploads/` — tieto priečinky **nie sú v gite** a žijú len na hostingu.

Overené na: Webglobe (dočasná ukážka `alfa.oravsky.sk`, PHP 8.4, nginx pred Apache). Pôvodný plán: Websupport.

## 1. Požiadavky na hosting
- PHP **8.1 alebo novšie**, rozšírenia `fileinfo`, `mbstring`, `json`; odporúčané `gd` (zmenšovanie fotiek) a `dom` (formátovaný text). Inštalácia ich overí sama.
- Apache s povoleným `.htaccess` (mod_rewrite; mod_expires a mod_headers sú voliteľné).
- **HTTPS certifikát** (Let's Encrypt) pre doménu aj subdoménu — admin bez HTTPS nefunguje.
- FTP/SFTP prístup. Databáza **netreba**.

## 2. Nahratie súborov
1. Stiahni z GitHubu vetvu **`cms-admin`** (Code → Download ZIP), alebo si nechaj pripraviť ZIP.
2. Cez (S)FTP nahraj **obsah** priečinka do koreňa webu (vrátane skrytých `.htaccess` a `.user.ini` — vo FileZille zapni zobrazovanie skrytých súborov).
   Netreba nahrávať: `.git/`, `docs/`, `tests/`, `_zaloha/`, `CLAUDE.md`.
3. Web musí bežať v **koreni** domény alebo subdomény (napr. Webglobe: subdoména `alfa` = priečinok `public_html/_sub/alfa`).

## 3. Nastavenie v kóde (`inc/site.php`)
- **Adresa webu sa zistí sama** (`SITE_URL` z adresy, na ktorej web beží) — pri presune na inú doménu netreba nič meniť.
  Pevnú adresu (napr. len `https://www.…`) nastavíš v `SITE_URL_FIXED`.
- **Indexovanie** (`SITE_INDEX`) je automaticky zapnuté na ostrej doméne a vypnuté na dočasných ukážkach (`SITE_DEMO_HOSTS`, teraz `alfa.oravsky.sk`) a lokálne.
- `CLUB` — fakty o klube (adresa, tréningy, kontakt) pre Google a AI. Pri zmene uprav aj texty stránok.

## 4. Prvé spustenie (nastavenie e-mailu a hesla)
1. Na počítači v priečinku repa: `php tools/new-install-token.php <doména>` → vznikne `install-token.php` a vypíše sa odkaz `https://<doména>/install.php?token=…`.
2. Nahraj `install-token.php` do koreňa webu.
3. Otvor odkaz. Skontroluj riadok **„Tvoja zistená IP adresa“** (má sedieť s „what is my ip“).
4. Zadaj e-mail a heslo trénera (aspoň 8 znakov, veľké aj malé písmeno, číslica, špeciálny znak). Ukážkový obsah je voliteľný.
5. Prihlásenie: `https://<doména>/admin/`. Token sa sám zmaže.

## 5. Kontroly po nasadení
Najspoľahlivejšie: end-to-end test v prehliadači `node tools/e2e/e2e.mjs https://<doména> cookies.txt <foto.jpg>`
(postup v `tools/e2e/README.md`) — vytvorí koncept, zverejní ho s fotkou, album s fotkou, a všetko zas zmaže. Ručne:
- `https://<doména>/data/auth.json`, `/data/posts.json` → **403 alebo 404** (v `data/` je aj kôš zmazaných fotiek `data/trash/`).
  Ak vráti **200** (nginx servíruje súbory priamo): vytvor priečinok `alfa-data` **o úroveň vyššie** ako koreň webu, presuň doň obsah `data/` a `data/` zmaž.
- `/inc/auth.php`, `/install-token.php` → **403**; `http://…/admin/` → presmeruje na `https://`.
- Domov, Aktuality, Galéria, Kontakt sa načítajú; `/robots.txt`, `/sitemap.xml`, `/llms.txt` vrátia text.
- Prihlásenie do adminu prejde (nehlási „Platnosť formulára vypršala“).

## 6. Riešenie problémov
- **Chyba 500 hneď po nahratí** — zakomentuj (`#`) v `.htaccess` riadky `Options -Indexes` a `AddDefaultCharset UTF-8`; inak pozri chybový log hostingu.
- **„Príliš veľa presmerovaní“ na /admin/** — proxy neoznamuje HTTPS. Zakomentuj 4 riadky bloku „Admin a inštalácia len cez HTTPS“ v `.htaccess`; ak nepomôže, v `inc/bootstrap.php` vo `require_https()` daj na začiatok `return;`.
- **Fotky sa nenahrajú („Fotku sa nepodarilo nahrať“)** — admin posiela fotky ako base64 v textových poliach formulára
  a ukladá ich cez vlastný `data/tmp`, takže nepotrebuje dočasný priečinok PHP (Webglobe ho má nezapisovateľný; JSON nad ~16 kB
  by tam PHP zahodil). Skontroluj, že `data/` a `uploads/` sú zapisovateľné. Fotka po zmenšení musí byť pod 5 MB (bežne 100–400 kB).
- **„Platnosť formulára vypršala“ pri každom odoslaní** — relácie sa neukladajú. Web ich ukladá sám do `data/sessions` (nezávisle od nastavení hostingu); skontroluj, že `data/` je zapisovateľný.
- **Certifikát pre subdoménu** — vo Webglobe: Hosting → Web → Subdomény (vytvoriť) a SSL certifikáty (vystaviť aj pre subdoménu); trvá pár minút.

## 7. Zálohy a obnova
- Zálohuj `data/` (alebo `../alfa-data/`) a `uploads/`. Zálohy pred zmazaním (`data/backup/*-pred-zmazanim-*.json`) sa držia 30 dní — raz za čas ich stiahni cez FTP. Hosting robí aj vlastné denné zálohy.
- Pri každom uložení vzniká záloha v `data/backup/` (napr. `posts-20261012-181500-ab12cd.json`). Poškodený `data/posts.json` / `albums.json` nahraď najnovšou zálohou.
- **Zmazané fotky** sa 30 dní držia v koši `data/trash/` ako `<čas>__posts__p_abc.webp` — pri obnove zálohy ich presuň späť (`__` → `/`, napr. do `uploads/posts/p_abc.webp`).

## 8. Aktualizácia kódu
Nahraj zmenené súbory cez FTP. **Nikdy neprepisuj ani nemaž** `data/`, `../alfa-data/` a `uploads/`.
CSS a JS majú v adrese verziu (`?v=…`), takže návštevníci po nahratí hneď dostanú novú verziu.

## 9. Zabudnutý e-mail alebo heslo
Zmaž cez FTP `data/auth.json` a zopakuj časť 4 (nový token). Existujúce aktuality a fotky ostanú.

---

## 10. Presun z dočasnej ukážky na trénerov web (ostrá doména)

Cieľ: web beží na trénerovom hostingu a vlastnej doméne, s obsahom, ktorý tréner medzitým pridal na ukážke.

1. **Pripraviť hosting** podľa časti 1 (PHP 8.1+, HTTPS pre doménu aj `www`).
2. **Nahrať kód** podľa časti 2.
3. **Adresu ani indexovanie netreba meniť** — `inc/site.php` ich zistí sám (indexuje sa všade okrem `SITE_DEMO_HOSTS`).
   Jednoduchý postup pre trénera (FileZilla, inštalačný odkaz, obrázky): `NASADENIE-PRE-TRENERA.md`.
4. **Preniesť obsah** — z ukážky (`public_html/_sub/alfa/`) stiahnuť celé `data/` a `uploads/` (okrem prípadného `uploads/.trash/` — starý kôš sa neprenáša) (vrátane `data/auth.json`, takže e-mail a heslo trénera ostanú) a nahrať ich na nový hosting do koreňa webu.
   Ak chce tréner začať načisto: obsah nepreniesť a spraviť časť 4 (nový token, bez ukážkového obsahu).
5. **Skontrolovať** podľa časti 5 a prihlásiť sa do `https://<nová-doména>/admin/`.
   Tréner sa na každom zariadení prihlási znova (nová doména = nové cookies).
6. **Google a AI**:
   - over `https://<doména>/robots.txt` (má obsahovať `Allow` a riadok `Sitemap:`) a `https://<doména>/sitemap.xml`;
   - pridaj doménu do **Google Search Console** (overenie cez DNS záznam TXT u registrátora) a odošli `sitemap.xml`;
   - založ/aktualizuj **Firemný profil Google** (Google Maps) s rovnakou adresou, telefónom a odkazom na web;
   - voliteľne Bing Webmaster Tools (z neho čerpá aj ChatGPT vyhľadávanie) — dá sa importovať zo Search Console.
7. **Ukážku vypnúť** — na `alfa.oravsky.sk` nahraj do koreňa len `.htaccess` s presmerovaním:
   ```
   RewriteEngine On
   RewriteRule ^(.*)$ https://<nová-doména>/$1 [R=301,L]
   ```
   (alebo subdoménu vo Webglobe zmaž). Až potom zmaž jej `data/` a `uploads/`.
8. **GitHub Pages** (stará statická verzia na `lukaskooravsky.github.io`) vypnúť: GitHub → Settings → Pages. Potom zlúčiť vetvu `cms-admin` do `main`.
