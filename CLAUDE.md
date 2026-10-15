# ALFA Taekwondo Club Bratislava — web (náčrt / demo)

Statický web pre klub **ALFA Taekwondo Club Bratislava** (ITF Taekwon-Do).
Zatiaľ ide o **náčrt (demo)** — obsah aj fotky sú ukážkové, formuláre nie sú
naostro napojené. Slúži na to, aby si to tréner pozrel a schválil.

## 🌐 Naživo (GitHub Pages)
**https://lukaskooravsky.github.io/taekwondo-alfa-klub/**
Repozitár: `lukaskooravsky/taekwondo-alfa-klub` (vetva `main`, Pages: main / root).

## Technológie
- **HTML + CSS + JS + PHP** (bez frameworku, bez databázy, bez buildu). Aktuality a galéria sa
  spravujú cez admin `/admin/` (prihlásenie **e-mail + heslo**) a ukladajú do `data/*.json` + `uploads/` — tie nie sú v gite.
- Hosting: **Websupport** (PHP 8.3+, SFTP). Nasadenie: `NASADENIE.md`.
- Lokálne spustenie: `php -S localhost:8000`, testy: `php tests/run.php`. PHP 8.3 je prenosné
  v `C:\Users\lukas\tools\php83` (v PATH cez `~/.bashrc`; v neinteraktívnom shelli treba
  `export PATH="/c/Users/lukas/tools/php83:$PATH"`). `php -S` ignoruje `.htaccess`.
- Test admina z mobilu cez wifi: `ALFA_DEV_LAN=1 php -S 0.0.0.0:8001` → `http://<IP-PC>:8001/admin/`
  (bez premennej admin mimo localhostu presmeruje na HTTPS).
- Heslo: aspoň 8 znakov, veľké + malé písmeno, číslica, špeciálny znak, bez časti e-mailu pred @
  (`auth_password_error` v `inc/auth.php` + živá kontrola v `admin/admin.js`). Lokálne testovacie
  prihlásenie je len v lokálnom `data/auth.json` (nie v gite).
- Návrh: `docs/superpowers/specs/2026-10-12-cms-admin-design.md`.
- Fonty sú **self-hostované** v `assets/fonts/` (načítané cez `css/fonts.css`) —
  bez volania Google (kvôli GDPR aj spoľahlivému renderu).
- `.nojekyll` vypína spracovanie cez Jekyll (aby Pages servírovalo súbory 1:1).

## Štruktúra
```
co-je-taekwondo.html  Čo je taekwondo
pasy.html             Pásy (systém stupňov gup/dan)
o-nas.html            O nás (tréner + rozvrh)
kontakty.html         Kontakt + prihláška na skúšobnú hodinu
css/style.css         hlavný štýl (dizajnové premenné hore)
css/fonts.css         @font-face pravidlá (self-hosted)
js/main.js            menu, scroll-reveal, lightbox, obsluha formulárov
assets/               obrázky + fonty
index.php             Domov (3 najnovšie aktuality + fotky z najnovšieho albumu)
aktuality.php         Aktuality (z data/posts.json)
aktualita.php?id=…    Detail aktuality
galeria.php           Galéria — albumy; ?album=… fotky albumu (lightbox)
inc/                  PHP logika (store, posts, albums, upload, auth, render, layout, site.php = SITE_DEMO)
admin/                administrácia (login, aktuality, albumy, upload API)
install.php           prvé nastavenie hesla (len s tokenom z tools/new-install-token.php)
tools/                new-install-token.php — spúšťa sa lokálne
tests/run.php         testy (php tests/run.php [filter])
```

## Dizajn (premenné v `css/style.css`)
- Farby: čierna `#0a0a0c`, zlatá `#c8a04e` / `#e6bd6a`, kosť/biela `#f3efe6`.
- Text: nadpisy biele, telový/sekundárny text `--muted` (`#c3bdb1` — zosvetlené 2026-09-24
  kvôli čitateľnosti sivej na čiernom pozadí; predtým `#9c968a` bolo slabo vidno na mobile),
  najtlmenejšie `--muted-2` (`#948e83`). Pri zmene farby textu drž kontrast na čiernom.
- Fonty: **Saira Condensed** (nadpisy), **Barlow** (text), **Noto Sans KR** (kórejské akcenty 태권도).
- Uhlové rezy cez `--cut` (clip-path).

## Ako upraviť obsah
Uprav príslušný `.html` súbor a **daj na git** (viď nižšie) — Pages sa prebuduje
sám do 1–2 minút.
- **Aktuality a galéria**: spravuje tréner v `/admin/`. V kóde sa nemenia. Na GitHub Pages
  (bez PHP) sa nezobrazujú — fungujú až na Websupporte.
- **Rozvrh**: tabuľka `.schedule` v `o-nas.html` a `index.php`.
- **Kontakty a menu**: telefón/e-mail/adresa a odkazy menu sú v pätičke/hlavičke každej `.html` stránky
  **a zároveň** v `inc/layout-header.php` + `inc/layout-footer.php` (PHP stránky) — meň oboje. Kontakt aj na `kontakty.html`.
- **Fotky**: nahraj do `assets/` a v HTML zmeň `src`. Hlavná akčná fotka je `assets/extrakt.png`.
- **Hero na mobile**: má fotku na pozadí za textom (`.hero-bg` v `css/style.css`, len ≤820px,
  so stmavením kvôli čitateľnosti). Teraz používa `assets/extrakt.png`; zmeníš v `.hero-bg`.
- **Disciplíny** (`index.php`, sekcia `#discipliny`): 4 karty (Tul, Matsogi, Wi-ryok, Teuki).
  Fotka každej je inline `style="background-image:url('assets/disc-*.jpg')"` (nie cez CSS premennú —
  relatívna URL v premennej sa rieši voči `css/` a fotka by sa nenačítala): `disc-tul.jpg`,
  `disc-matsogi.jpg`, `disc-wiryok.jpg`, `disc-teuki.jpg`. Kým chýbajú, je tam tmavý placeholder.

> ⚠️ **Kórejské znaky:** fonty sú self-hostované *subsety* — obsahujú len znaky reálne
> použité v obsahu (태권도, 무도, 太). Nový KR znak sa zobrazí ako prázdny štvorček (□).
> V nových sekciách preto KR akcenty nepoužívaj (alebo regeneruj subset cez `pyftsubset`).

## Čo ešte treba doriešiť (pred ostrým spustením)
- [ ] Reálne tréningové fotky (nahradiť ukážkové), **logo v priehľadnom PNG**.
- [x] Formuláre (kontakt + prihláška) odosielajú cez `mailto:` na `lub.zavodny1@gmail.com`
      (obsluha v `js/main.js`). Do budúcna sa dá prejsť na FormSubmit/Formspree bez `mailto:`.
- [ ] Zásady ochrany osobných údajov (GDPR), súhlasy vrátane detí (rodič).
- [ ] Vlastná doména (zatiaľ nekúpená) — potom nastaviť v Pages (CNAME).
- [ ] Skontrolovať/upraviť texty (Čo je taekwondo, Pásy, O nás — teraz sú to návrhy).
- [x] Admin na aktuality a galériu (PHP, `/admin/`) — hotový na vetve `cms-admin`.
- [ ] Nasadiť podľa `NASADENIE.md` — robí to **Brian** (programátor, vlastný zdieľaný hosting); Ľubo kupuje len
      doménu. Brianovi sa posiela odkaz na vetvu `cms-admin` na GitHube (Gmail ZIP s .js/.php blokuje).
      Potom vypnúť Pages a zlúčiť `cms-admin` do `main`.
- [ ] Pred ostrým spustením `SITE_DEMO = false` v `inc/site.php` + odstrániť demo pásik zo `.html`.

## Fakty o klube (aktuálne v obsahu)
- Tréner: **Ľubomír Závodný**, IV. Dan čierny pás, Majster Európy.
- Miesto: **ZŠ Holíčska 50, Bratislava — Petržalka**.
- Tréningy: **Utorok a Štvrtok, 16:30–19:30** — začiatočníci 16:30–17:30, stredne pokročilí 17:30–18:30,
  pokročilí a dospelí 18:30–19:30 (zmenené 2026-10-12).
- Kontakt: 0911 818 815, lub.zavodny1@gmail.com.

## Nasadenie / práca s gitom
Repozitár patrí účtu **lukaskooravsky**. Lokálna kópia: `C:\Users\lukas\projekty\taekwondo-alfa-klub`
(root repa = web, žiadny podpriečinok `site/`).

Na tomto počítači je `gh` prihlásené ako **lukaskooravsky** (HTTPS + git credential helper),
takže push je bežný:
```
git push origin main
```
GitHub Pages sa prebuduje do 1–2 minút.

Ak by prihlásenie vypršalo:
```
gh auth login -h github.com -w
gh auth setup-git -h github.com
```

> Staré postupy (push cez HTTPS token v URL, účet DusanOravsky so suspendovaným SSH)
> už neplatia. Tokeny nedávaj do URL ani do `.git/config`.
