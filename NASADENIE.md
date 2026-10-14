# Nasadenie webu ALFA Taekwondo na Websupport

## 1. Čo treba na Websupporte nastaviť (WebAdmin)
- **PHP 8.3 alebo novšie:** WebAdmin → doména → Web → Upraviť → PHP verzia → Uložiť
  (prejaví sa do ~15 minút). PHP 8.1 a staršie je za príplatok — netreba.
- **HTTPS:** Let's Encrypt certifikát a presmerovanie HTTP → HTTPS robí Websupport automaticky;
  skontroluj, že `https://tvoja-domena.sk` ide bez varovania.
- **SFTP prístup:** WebAdmin → FTP účty (prihlasovacie údaje, adresa servera). SSH netreba.
- Databáza **netreba**. Rozšírenia `fileinfo`, `mbstring`, `gd` sú bežne zapnuté — overí to inštalácia.

## 2. Nahratie súborov
1. Stiahni z GitHubu ZIP vetvy **`cms-admin`** (Code → Download ZIP pri zvolenej vetve).
   Do `main` sa vetva zlúči až **po** vypnutí GitHub Pages (viď časť 10) — Pages nevie PHP
   a bez `index.html` by tam ostala prázdna stránka.
2. Cez SFTP (napr. WinSCP, FileZilla) nahraj **obsah** priečinka do koreňa webu
   (na Websupporte priečinok webu domény, napr. `/web/`).
   Netreba nahrávať: `.git/`, `docs/`, `tests/`, `_zaloha/`.
3. Ak web nebeží v koreni domény ale v podpriečinku, uprav v `.htaccess` riadok `RewriteBase /`.

## 3. Prvé spustenie (nastavenie hesla)
1. Na svojom počítači v priečinku repa spusti:
   `php tools/new-install-token.php tvoja-domena.sk`
   Vznikne súbor `install-token.php` a vypíše sa odkaz `https://tvoja-domena.sk/install.php?token=…`.
2. Nahraj `install-token.php` cez SFTP do koreňa webu (vedľa `install.php`).
3. Otvor vypísaný odkaz. Skontroluj riadok **„Tvoja zistená IP adresa“** — má sedieť s tým, čo ukáže
   napr. vyhľadanie „what is my ip“. (Ak ukazuje `127.0.0.1` alebo `10.…`, blokácia zlých hesiel by
   platila pre všetkých naraz — daj vedieť programátorovi.)
   Over aj, že sa adresa nedá podvrhnúť (na svojom počítači, s odkazom z kroku 1):
   `curl -s -H 'X-Real-IP: 1.2.3.4' -H 'X-Forwarded-For: 1.2.3.4' "<odkaz>" | grep -o 'zistená IP adresa: <b>[^<]*'`
   Nesmie vypísať `1.2.3.4`. Ak vypíše, nepokračuj a daj vedieť programátorovi.
4. Zadaj heslo pre trénera (aspoň 10 znakov). Ukážkový obsah môžeš nechať nahrať a neskôr ho zmazať.
5. Presmeruje ťa na prihlásenie do `https://tvoja-domena.sk/admin/`. Token sa sám zmazal —
   ak by `install-token.php` na serveri ostal, zmaž ho cez SFTP.

## 4. Kontroly po nasadení
- `https://tvoja-domena.sk/data/posts.json`, `…/data/auth.json` a `…/data/backup/` → **403 alebo 404**
  (dáta a hash hesla nie sú verejné).
  **Ak niektorý vráti 200** (súbory servíruje priamo nginx a `.htaccess` sa neuplatní): cez SFTP vytvor
  priečinok `alfa-data` **o úroveň vyššie ako koreň webu** (vedľa priečinka webu, nie v ňom), presuň doň
  celý obsah `data/` a priečinok `data/` zmaž. Web potom automaticky ukladá dáta tam (mimo dosahu
  prehliadača). Kontrolu zopakuj.
- `https://tvoja-domena.sk/inc/auth.php` a `https://tvoja-domena.sk/install-token.php` → **403**.
- `http://tvoja-domena.sk/admin/` → presmeruje na `https://…`.
- Domov, Aktuality, Galéria sa načítajú a fotky sa zobrazia.
- V `inc/site.php` je pred ostrým spustením `SITE_DEMO = false` (zmizne pásik „Náhľad webu“);
  pásik odstráň aj zo statických `.html` stránok.

## 5. Chyba 500 hneď po nahratí
Takmer vždy ide o riadok v `.htaccess`, ktorý hosting nepovolí. Skús dočasne zakomentovať (`#`)
riadky `Options -Indexes` a `AddDefaultCharset UTF-8`. Ak to nepomôže, pozri chybový log
vo WebAdmine (Logy).

## 5a. „Príliš veľa presmerovaní“ (ERR_TOO_MANY_REDIRECTS) na /admin/ alebo install.php
Proxy hostingu neoznamuje PHP, že stránka ide cez HTTPS. Dočasne zakomentuj (`#`) 4 riadky bloku
„Admin a inštalácia len cez HTTPS“ v `.htaccess`. Ak to nepomôže, v `inc/bootstrap.php` vo funkcii
`require_https()` pridaj na začiatok `return;` (HTTPS aj tak vynucuje Websupport sám) a daj vedieť
programátorovi.

## 6. Čo zálohovať
Obsah od trénera je **len na hostingu**, nie v gite:
- `data/` (alebo `../alfa-data/`, ak si dáta presunul podľa časti 4) — aktuality, albumy, heslo (`auth.json`, zahashované) + automatické zálohy v `data/backup/`
- `uploads/` — fotky

Websupport robí denné zálohy; navyše si raz za čas tieto priečinky stiahni cez SFTP.

## 7. Aktualizácia kódu webu
Nahraj zmenené súbory cez SFTP. **Nikdy neprepisuj ani nemaž** `data/` (resp. `../alfa-data/`) a `uploads/`.

## 8. Obnova zo zálohy
Ak sa `data/posts.json` alebo `data/albums.json` poškodí, admin to ohlási (alebo `../alfa-data/`, ak si dáta presunul podľa časti 4). Skopíruj najnovší súbor
z `data/backup/` (napr. `posts-20261012-181500-ab12cd.json`) späť ako `data/posts.json`.

## 9. Zabudnuté heslo
Zmaž cez SFTP `data/auth.json` (alebo `../alfa-data/`, ak si dáta presunul podľa časti 4) a zopakuj časť 3 (nový token). Ukážkový obsah vtedy nezaškrtávaj —
existujúce dáta by sa aj tak neprepísali.

## 10. Doména a GitHub Pages
Doménu kúp/nasmeruj vo Websupporte (WebAdmin → doména → DNS). Starý web na GitHub Pages potom vypni
(GitHub → Settings → Pages) a až potom zlúč vetvu `cms-admin` do `main`.
