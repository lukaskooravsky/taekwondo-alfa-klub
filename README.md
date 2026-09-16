# ALFA Taekwondo Club Bratislava — náčrt webu (demo)

Statický náhľad webu pre klub **ALFA Taekwondo Club Bratislava** (ITF
Taekwon-Do). Čisté HTML/CSS/JS — žiadny build, žiadne závislosti.

## Stránky

- `index.html` — Domov
- `co-je-taekwondo.html` — Čo je taekwondo
- `pasy.html` — Pásy (systém stupňov ITF)
- `aktuality.html` — Aktuality
- `galeria.html` — Galéria (s lightboxom)
- `o-nas.html` — O nás (tréner + rozvrh)
- `kontakty.html` — Kontakt + prihláška na skúšobnú hodinu

## Ako to spustiť lokálne

Stačí otvoriť `index.html` v prehliadači. Alebo spusti malý server:

```bash
# v priečinku site/
python3 -m http.server 8000
# potom otvor http://localhost:8000
```

## Ako to nahrať na GitHub Pages (aby to tréner videl)

1. Vytvor nový repozitár na GitHube (napr. `alfa-taekwondo`).
2. Nahraj doň **obsah tohto priečinka `site/`** (súbory `index.html`, `css/`,
   `js/`, `assets/` … musia byť v koreni repozitára).
3. V repozitári choď na **Settings → Pages**.
4. Pri *Source* zvoľ **Deploy from a branch**, branch `main`, priečinok
   `/ (root)`, a ulož.
5. O chvíľu bude web na `https://<tvoje-meno>.github.io/alfa-taekwondo/`.

> Tip: ak chceš, aby web fungoval z koreňa (`.github.io`), pomenuj repozitár
> `<tvoje-meno>.github.io`.

## Poznámky (demo vs. ostrý web)

- **Obsah a fotky sú ukážkové.** Reálne texty doladí tréner, fotky do galérie
  a logo vo vektore/PNG dodá tréner.
- **Formuláre zatiaľ nič neodosielajú** — ukazujú len správanie. Naostro sa
  napoja pri spustení na vlastnej doméne.
- **Aktuality** si bude tréner spravovať sám až na ostrom webe (spôsob správy
  vyberieme podľa zvoleného hostingu).
- **Fonty** (Saira Condensed, Barlow, Noto Sans KR) sú self-hostované
  v `assets/fonts/` a načítané cez `css/fonts.css` — bez volania Google
  (kvôli GDPR aj spoľahlivému renderu).

## Branding

- Farby: čierna, zlatá/béžová (`#c8a04e`), biela.
- Písmo: Saira Condensed (nadpisy) + Barlow (text), kórejské akcenty 태권도.
