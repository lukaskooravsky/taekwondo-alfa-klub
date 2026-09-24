# ALFA Taekwondo Club Bratislava — web (náčrt / demo)

Statický web pre klub **ALFA Taekwondo Club Bratislava** (ITF Taekwon-Do).
Zatiaľ ide o **náčrt (demo)** — obsah aj fotky sú ukážkové, formuláre nie sú
naostro napojené. Slúži na to, aby si to tréner pozrel a schválil.

## 🌐 Naživo (GitHub Pages)
**https://lukaskooravsky.github.io/taekwondo-alfa-klub/**
Repozitár: `lukaskooravsky/taekwondo-alfa-klub` (vetva `main`, Pages: main / root).

## Technológie
- Čisté **HTML + CSS + JS**, žiadny build krok (netreba Node ani nič inštalovať).
- Fonty sú **self-hostované** v `assets/fonts/` (načítané cez `css/fonts.css`) —
  bez volania Google (kvôli GDPR aj spoľahlivému renderu).
- `.nojekyll` vypína spracovanie cez Jekyll (aby Pages servírovalo súbory 1:1).

## Štruktúra
```
index.html            Domov
co-je-taekwondo.html  Čo je taekwondo
pasy.html             Pásy (systém stupňov gup/dan)
aktuality.html        Aktuality
galeria.html          Galéria (lightbox)
o-nas.html            O nás (tréner + rozvrh)
kontakty.html         Kontakt + prihláška na skúšobnú hodinu
css/style.css         hlavný štýl (dizajnové premenné hore)
css/fonts.css         @font-face pravidlá (self-hosted)
js/main.js            menu, scroll-reveal, lightbox, obsluha formulárov
assets/               obrázky + fonty
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
- **Aktuality**: príspevky sú `<article class="post">` v `aktuality.html` (a teaser v `index.html`).
- **Rozvrh**: tabuľka `.schedule` v `o-nas.html` a `index.html`.
- **Kontakty**: telefón/e-mail/adresa v pätičke každej stránky + na `kontakty.html`.
- **Fotky**: nahraj do `assets/` a v HTML zmeň `src`. Hlavná akčná fotka je `assets/extrakt.png`.
- **Hero na mobile**: má fotku na pozadí za textom (`.hero-bg` v `css/style.css`, len ≤820px,
  so stmavením kvôli čitateľnosti). Teraz používa `assets/extrakt.png`; zmeníš v `.hero-bg`.
- **Disciplíny** (`index.html`, sekcia `#discipliny`): 4 karty (Tul, Matsogi, Wi-ryok, Teuki).
  Fotku každej doplníš cez inline `--img:url('assets/disc-*.jpg')`: `disc-tul.jpg`,
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

## Fakty o klube (aktuálne v obsahu)
- Tréner: **Ľubomír Závodný**, IV. Dan čierny pás, Majster Európy.
- Miesto: **ZŠ Holíčska 50, Bratislava — Petržalka**.
- Tréningy: **Utorok a Štvrtok, 17:00–19:00** (deti 17–18, pokročilí+dospelí 18–19).
- Kontakt: 0911 818 815, lub.zavodny1@gmail.com.

## Nasadenie / práca s gitom (DÔLEŽITÉ — čítaj)
Repozitár patrí účtu **lukaskooravsky**. Na tomto počítači je `gh` prihlásené ako
**DusanOravsky**, ktorý má na repe len **READ** a jeho **SSH kľúč je pozastavený**
(„account is suspended"). Preto sa **priamy push cez SSH ani cez DusanOravsky nedá.**

Push funguje len **ako lukaskooravsky cez HTTPS token**:
1. Prihlás sa v prehliadači ako lukaskooravsky → https://github.com/settings/tokens
   → *Tokens (classic)* → scope **`repo`** → vygeneruj token `ghp_…`.
2. Push (token sa NEukladá do configu):
   ```
   git -c credential.helper= push \
     "https://lukaskooravsky:<TOKEN>@github.com/lukaskooravsky/taekwondo-alfa-klub.git" \
     main:main
   ```
3. **Token potom zmaž/revokni** na tej istej stránke (Tokens).

> Alternatíva do budúcna: nech lukaskooravsky pridá DusanOravsky ako collaboratora
> s právom **Write** a vyrieši sa suspendovaný SSH kľúč — potom by šiel bežný `git push`.
