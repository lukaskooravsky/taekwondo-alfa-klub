---
name: project-alfa-taekwondo-web
description: "ALFA Taekwondo Club Bratislava — statický web (náčrt/demo), stav a kde beží"
metadata: 
  node_type: memory
  type: project
  originSessionId: 5ec1f520-6c77-43e6-9002-0d521bb3d45e
  modified: 2026-09-24T13:55:04.725Z
---

Web pre klub **ALFA Taekwondo Club Bratislava** (ITF Taekwon-Do), robený pre používateľovho trénera. Ide o **náčrt/demo** na schválenie trénerom (obsah a fotky ukážkové, formuláre nie sú naostro napojené).

- **Technológia:** čisté HTML/CSS/JS, žiadny build. Fonty self-hostované v `assets/fonts/` cez `css/fonts.css` (bez Google, kvôli GDPR).
- **Zdroj:** `/home/cvcta/projects/learning/luki-takewondoo/site/` (samostatný git repo, root = site). Podrobnosti v `site/CLAUDE.md`.
- **Naživo:** https://lukaskooravsky.github.io/taekwondo-alfa-klub/ (GitHub Pages, vetva `main`, main/root).
- **7 stránok:** Domov, Čo je taekwondo, Pásy, Aktuality, Galéria, O nás, Kontakt.
- **Fakty v obsahu:** tréner Ľubomír Závodný (IV. Dan, Majster Európy); ZŠ Holíčska 50, Petržalka; tréningy Ut+Št 17:00–19:00; 0911 818 815.

**Otvorené úlohy pred ostrým spustením:** reálne fotky + logo v priehľadnom PNG, naozaj napojiť formuláre, GDPR/zásady, vlastná doména (zatiaľ nekúpená). Používateľ nechce WordPress.

**Feedback od trénera (2026-09-24, zapracované):** (1) hero na mobile má fotku kopajúcich taekwondistov ako pozadie za textom (à la tkdpezinok.sk) — cez `.hero-bg` v `css/style.css`, používa **existujúcu `assets/extrakt.png`** (tréner chcel túto fotku, negenerovali sme novú); zobrazuje sa len ≤820px so stmavením kvôli čitateľnosti; (2) v mobilnom menu oddeľovacia čiara nesmie ísť cez text — opravené `display:block` na `.nav-links a`; (3) pridaná sekcia „Disciplíny ITF" na `index.html` so 4 kartami (Tul, Matsogi, Wi-ryok, Teuki), CSS `.disc*`. Karty majú zatiaľ tmavý placeholder s watermarkom; reálne fotky sa dopĺňajú ako `assets/disc-tul.jpg`, `disc-matsogi.jpg`, `disc-wiryok.jpg`, `disc-teuki.jpg` (otvorená otázka: či dovtedy dať extrakt.png ako dočasnú, alebo nechať prázdne).

**Nová požiadavka (2026-09-24, ODLOŽENÁ):** tréner chce **admin sekciu / CMS** na správu príspevkov, fotiek a albumov. Statický web nemá backend — bude treba git-CMS (Decap na Pages cez GitHub OAuth, alebo presun na Netlify + Decap/Identity). Používateľ zvolil zatiaľ neriešiť, spraviť neskôr ako samostatný krok. Súvisí so skorším návrhom „Astro + Netlify + git-CMS (voľba A)".

Pozor na fonty: viď [[fonts-kr-subset-gotcha]]. Nasadenie a git gotcha viď [[deploy-git-lukasko-token]].
