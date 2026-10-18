---
name: feedback-ukladaj-priebezne
description: "Používateľ chce, aby sa všetko priebežne ukladalo (commit+push, pamäť)"
metadata:
  node_type: memory
  type: feedback
  originSessionId: b3956cdb-161a-4185-8cf7-509308ed727e
  modified: 2026-10-18T20:44:09.422Z
---

Všetko priebežne ukladaj: po každom ucelenom kroku commit + push na aktuálnu vetvu a aktualizuj pamäť so stavom práce.
**Why:** používateľ to výslovne žiadal (2026-10-12) — nechce o nič prísť a chce vidieť na GitHube, že to prechádza.
**How to apply:** nečakaj na koniec veľkej úlohy; po opravách review, po každom tasku plánu → commit + push ([[deploy-git-lukasko-token]]), stav do [[project-alfa-taekwondo-web]].
Na konci väčšej práce „aktualizuj všetko“ = git + FTP (alfa.oravsky.sk) + CLAUDE.md/NASADENIE.md + pamäť aj jej záloha v repe `_zaloha/memory/` (žiadal 2026-10-18).
