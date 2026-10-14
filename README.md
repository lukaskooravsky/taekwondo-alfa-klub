# ALFA Taekwondo Club Bratislava — náčrt webu (demo)

Web pre klub **ALFA Taekwondo Club Bratislava** (ITF Taekwon-Do). HTML/CSS/JS + malý PHP admin
na aktuality a galériu — bez frameworku, bez databázy, bez buildu.

## Stránky

- `index.php` — Domov (najnovšie aktuality a fotky)
- `co-je-taekwondo.html` — Čo je taekwondo
- `pasy.html` — Pásy (systém stupňov ITF)
- `aktuality.php`, `aktualita.php?id=…` — Aktuality a detail
- `galeria.php` — Galéria (albumy, lightbox)
- `o-nas.html` — O nás (tréner + rozvrh)
- `kontakty.html` — Kontakt + prihláška na skúšobnú hodinu
- `admin/` — administrácia aktualít a galérie (heslo)

## Ako to spustiť lokálne

Treba PHP 8.1+ (napr. `winget install PHP.PHP.8.3` alebo ZIP z windows.php.net). V koreni repa:

```bash
php -S localhost:8000
# potom otvor http://localhost:8000
php tests/run.php   # testy
```

## Nasadenie

Web beží na hostingu **Websupport** — postup je v `NASADENIE.md`.

## Poznámky (demo vs. ostrý web)

- **Obsah a fotky sú ukážkové.** Reálne texty doladí tréner, fotky do galérie
  a logo vo vektore/PNG dodá tréner.
- **Formuláre** (kontakt, prihláška) otvoria e-mail cez `mailto:`.
- **Aktuality a galériu** si tréner spravuje sám v `/admin/` (z mobilu aj počítača).
- **Fonty** (Saira Condensed, Barlow, Noto Sans KR) sú self-hostované
  v `assets/fonts/` a načítané cez `css/fonts.css` — bez volania Google
  (kvôli GDPR aj spoľahlivému renderu).

## Branding

- Farby: čierna, zlatá/béžová (`#c8a04e`), biela.
- Písmo: Saira Condensed (nadpisy) + Barlow (text), kórejské akcenty 태권도.
