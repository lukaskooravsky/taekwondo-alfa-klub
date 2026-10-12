---
name: project-alfa-taekwondo-web
description: "ALFA Taekwondo Club Bratislava — statický web (náčrt/demo), stav a kde beží"
metadata: 
  node_type: memory
  type: project
  originSessionId: 5ec1f520-6c77-43e6-9002-0d521bb3d45e
  modified: 2026-10-12T17:33:16.000Z
---

Web pre klub **ALFA Taekwondo Club Bratislava** (ITF Taekwon-Do), robený pre používateľovho trénera. Ide o **náčrt/demo** na schválenie trénerom (obsah a fotky ukážkové, formuláre nie sú naostro napojené).

- **Technológia:** čisté HTML/CSS/JS, žiadny build. Fonty self-hostované v `assets/fonts/` cez `css/fonts.css` (bez Google, kvôli GDPR).
- **Zdroj:** `C:\Users\lukas\projekty\taekwondo-alfa-klub\` (klon z GitHubu 2026-10-12, root repa = web; pôvodne `/home/cvcta/projects/learning/luki-takewondoo/site/` na inom stroji). Podrobnosti v `CLAUDE.md`.
- **Naživo:** https://lukaskooravsky.github.io/taekwondo-alfa-klub/ (GitHub Pages, vetva `main`, main/root).
- **7 stránok:** Domov, Čo je taekwondo, Pásy, Aktuality, Galéria, O nás, Kontakt.
- **Fakty v obsahu:** tréner Ľubomír Závodný (IV. Dan, Majster Európy); ZŠ Holíčska 50, Petržalka; tréningy Ut+Št 17:00–19:00; 0911 818 815.

**Otvorené úlohy pred ostrým spustením:** reálne fotky + logo v priehľadnom PNG, naozaj napojiť formuláre, GDPR/zásady, vlastná doména (zatiaľ nekúpená). Používateľ nechce WordPress.

**Feedback od trénera (2026-09-24, zapracované):** (1) hero na mobile má fotku kopajúcich taekwondistov ako pozadie za textom (à la tkdpezinok.sk) — cez `.hero-bg` v `css/style.css`, používa **existujúcu `assets/extrakt.png`** (tréner chcel túto fotku, negenerovali sme novú); zobrazuje sa len ≤820px so stmavením kvôli čitateľnosti; (2) v mobilnom menu oddeľovacia čiara nesmie ísť cez text — opravené `display:block` na `.nav-links a`; (3) pridaná sekcia „Disciplíny ITF" na `index.html` so 4 kartami (Tul, Matsogi, Wi-ryok, Teuki), CSS `.disc*`. Karty majú zatiaľ tmavý placeholder s watermarkom; reálne fotky sa dopĺňajú ako `assets/disc-tul.jpg`, `disc-matsogi.jpg`, `disc-wiryok.jpg`, `disc-teuki.jpg` (otvorená otázka: či dovtedy dať extrakt.png ako dočasnú, alebo nechať prázdne).

**Kontrast textu (2026-09-24, zapracované):** tréner/používateľ hlásili, že sivé a zlaté písmo na čiernom pozadí je na mobile slabo vidno (hlavne lead odsek v úvode, popisy kariet, adresa/čas v páse, disciplíny). Opravené jedným ťahom — zosvetlená premenná `--muted` (`#9c968a` → `#c3bdb1`) a `--muted-2` (`#6f6a61` → `#948e83`) v `css/style.css`. Nadpisy ostali biele, zlaté akcenty zlaté, hierarchia drží. Nasadené naživo.

**CMS admin (rozbehnuté 2026-10-12):** web sa presunie na klasický PHP hosting + vlastnú doménu (zatiaľ nevybrané/nekúpené). Zvolený vlastný PHP admin s heslom (len tréner), JSON súbory, len aktuality + albumy, zmenšovanie fotiek v prehliadači. Práca na vetve `cms-admin` (main/Pages nedotknuté). Spec: `docs/superpowers/specs/2026-10-12-cms-admin-design.md` (schválený), plán: `docs/superpowers/plans/2026-10-12-cms-admin.md` (14 taskov). Stav: beží kritická review specu+plánu — používateľ chce dotiahnuť na **0 critical/0 high/0 medium/0 low** pred implementáciou; spôsob vykonania (subagenti vs. priamo) ešte nevybral.

Pozor na fonty: viď [[fonts-kr-subset-gotcha]]. Nasadenie a git gotcha viď [[deploy-git-lukasko-token]].
