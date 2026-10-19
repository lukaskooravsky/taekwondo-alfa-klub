# Ako dať web ALFA Taekwondo na vlastnú doménu

Návod pre trénera. Zaberie asi 30 minút. **Programovať netreba a nikde netreba meniť adresu webu** —
web si svoju doménu zistí sám.

V balíku (ZIP) sú:
- priečinok **`web`** — celý web, ten sa nahrá na hosting,
- tento návod a **návod na administráciu** (písanie aktualít, galéria).

---

## 1. Čo treba mať

1. **Doménu** (napr. `alfataekwondo.sk`) — kúpiš u registrátora, často spolu s hostingom.
2. **Webhosting s PHP** — stačí najlacnejší bežný balík (Websupport, Webglobe, Active24 …). Pri objednávke/v nastaveniach skontroluj:
   - **PHP 8.1 alebo novšie** (ideálne najnovšie, napr. 8.3 / 8.4),
   - **SSL certifikát (HTTPS)** zapnutý pre doménu aj `www` — býva zadarmo („Let's Encrypt“), zapína sa jedným klikom.
     Bez HTTPS sa do administrácie neprihlásiš.
   - Databázu **netreba**.
3. **FTP prístup** (meno, heslo, server) — nájdeš v administrácii hostingu, často ho pošlú e-mailom.
4. Program **FileZilla** (zadarmo, https://filezilla-project.org — „FileZilla Client“).

## 2. Nahratie webu

1. ZIP rozbaľ (pravé tlačidlo → *Extrahovať všetko*).
2. Otvor FileZillu a hore vyplň **Hostiteľ** (FTP server), **Používateľ**, **Heslo** → *Rýchle pripojenie*.
3. Vo FileZille zapni **Server → Vynútiť zobrazovanie skrytých súborov**
   (web obsahuje skryté súbory `.htaccess` a `.user.ini` — bez nich nebude fungovať).
4. Vpravo (server) otvor priečinok webu domény — podľa hostingu sa volá napr. `public_html`, `www`, `web` alebo `htdocs`.
   Ak je v ňom ukážková stránka hostingu (`index.html`), zmaž ju.
5. Vľavo (tvoj počítač) otvor rozbalený priečinok **`web`**, označ **všetko vo vnútri** (Ctrl+A) a pretiahni doprava.
   Nahrávajú sa súbory *z vnútra* priečinka `web`, nie priečinok samotný.
6. Počkaj, kým dole zmizne fronta (pár minút).

## 3. Prvé spustenie — e-mail a heslo do administrácie

1. V prehliadači otvor (namiesto `TVOJA-DOMENA.sk` daj svoju doménu):

   **`https://TVOJA-DOMENA.sk/install.php?token=@@TOKEN@@`**

2. Zadaj svoj **e-mail** a **heslo** (aspoň 8 znakov, veľké aj malé písmeno, číslica a znak ako `!` alebo `*`; nesmie obsahovať časť e-mailu pred @).
3. Políčko **„Nahrať ukážkové aktuality a album“ ODŠKRTNI** — chceš čistý web.
4. Potvrď. Tento odkaz potom prestane fungovať (je jednorazový) — to je v poriadku.
5. Administrácia je na **`https://TVOJA-DOMENA.sk/admin/`** — ulož si ju do záložiek (aj v mobile).

## 4. Kontrola (2 minúty)

- Otvor `https://TVOJA-DOMENA.sk/data/auth.json` — musí sa ukázať **chyba** („Forbidden“ / „Not Found“).
  **Ak uvidíš text so zátvorkami `{ … }`, napíš Lukášovi** — hosting treba doladiť (ide o bezpečnosť hesla).
- Prejdi stránky Domov, Čo je taekwondo, Aktuality, Galéria, Kontakt.
- Prihlás sa do `/admin/`, skús pridať aktualitu s fotkou a zas ju zmazať.

---

## Kam dávať obrázky

| Čo | Kde / ako |
|---|---|
| **Fotky k aktualitám a do galérie** | Len cez **administráciu** (`/admin/`). Nikdy nie cez FTP. Fotky sa samy zmenšia, veľkosť nevadí. |
| **Fotky na stránkach** (úvodná akčná fotka, tréner, disciplíny) | Priečinok **`assets`** na FTP. Novú fotku nahraj **s presne rovnakým názvom** ako tá stará (prepíše ju). |
| **Logo** | Tiež `assets` (zoznam nižšie). |

Súbory v `assets`:
- `extrakt.jpg` — hlavná akčná fotka (Domov, O nás, Čo je taekwondo, pozadie na mobile), **na šírku cca 966 × 760**
- `disc-tul.jpg`, `disc-matsogi.jpg`, `disc-wiryok.jpg`, `disc-teuki.jpg` — disciplíny, **na šírku cca 840 × 560**
- `logo-mark.jpg` — logo v hlavičke a ikona webu; `logo-wordmark.jpg` — logo v pätičke; `logo-full.jpg` — veľké logo (náhľad pri zdieľaní na Facebooku)

Fotku pred nahratím do `assets` zmenši na uvedený rozmer (napr. v programe Fotky → *Zmeniť veľkosť*) a ulož ako **JPG**.
Ak zmenenú fotku hneď nevidíš, obnov stránku cez **Ctrl+F5**.

## Na čo si dať pozor

- **Priečinky `data` a `uploads` na FTP nikdy nemaž ani neprepisuj.** Sú v nich tvoje aktuality, albumy, fotky a heslo.
  (V ZIPe nie sú — vzniknú samy na hostingu.)
- Pri ďalšej úprave webu sa nahrávajú **len zmenené súbory** — nikdy nie znova všetko „načisto“ so zmazaním.
- **Záloha:** raz za pár mesiacov si cez FileZillu stiahni priečinky `data` a `uploads` k sebe do počítača.
- **Texty stránok** (Čo je taekwondo, Pásy, O nás, rozvrh, telefón) sú v súboroch `.php` — ak treba niečo zmeniť, radšej napíš Lukášovi,
  jeden zlý znak vie stránku rozbiť.
- **Fotky z iPhonu (HEIC)** na Windows nejdú — v iPhone *Nastavenia → Kamera → Formáty → Najkompatibilnejší*.
- **Zabudnuté heslo:** na FTP zmaž súbor `data/auth.json` a napíš Lukášovi o nový inštalačný odkaz (aktuality a fotky ostanú).
- Heslo do administrácie ani FTP nikomu neposielaj e-mailom/správou.

## Aby ťa našiel Google (po spustení)

1. **Firemný profil Google** (business.google.com) — klub s adresou ZŠ Holíčska 50, telefónom a odkazom na nový web.
   Toto najviac pomôže, aby sa klub ukazoval v Mapách a pri hľadaní „taekwondo Petržalka“.
2. **Google Search Console** (search.google.com/search-console) — pridaj doménu a odošli mapu webu
   `https://TVOJA-DOMENA.sk/sitemap.xml`. (Overenie domény vyžaduje záznam u registrátora — s tým pomôže Lukáš alebo podpora hostingu.)

## Keď niečo nejde

- **Chyba 500 hneď po nahratí** alebo **„Príliš veľa presmerovaní“** pri `/admin/` — hosting má iné nastavenie; napíš Lukášovi
  (postup je v technickom návode `NASADENIE.md` v repozitári).
- **Inštalačný odkaz hlási „Inštalácia je zamknutá“** — chýba súbor `install-token.php` (nahraj ho z priečinka `web` znova)
  alebo už inštalácia prebehla.
- **Fotka sa nenahrá** — skús menšiu/JPG; ak to nepomôže, napíš Lukášovi.
