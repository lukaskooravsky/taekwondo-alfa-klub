---
name: fonts-kr-subset-gotcha
description: "ALFA web: KR fonty sú subsety — nové kórejské znaky sa zobrazia ako prázdne štvorčeky"
metadata:
  node_type: memory
  type: project
  originSessionId: ec52be46-1d7f-4724-b5e6-8cb4ff5c79ba
  modified: 2026-09-24T13:43:40.802Z
---

Na ALFA webe ([[project-alfa-taekwondo-web]]) sú fonty **self-hostované subsety** v `site/assets/fonts/` (načítané cez `css/fonts.css`). Noto Sans KR subset obsahuje **len znaky reálne použité v obsahu** — v podstate 태권도, 무도 a 太.

**Dôsledok:** ak do HTML pridáš akýkoľvek nový kórejský znak (napr. 경기, 틀, 맞서기, 위력, 특기), zobrazí sa ako **prázdny štvorček (tofu, □)**, lebo v subsete nie je.

**Ako sa vyhnúť:** buď použi len už zahrnuté znaky, alebo KR akcent vypusti a použi latinku/`--font-display`. Alternatíva (ťažšia): regenerovať subset cez `pyftsubset` z pôvodného fontu s pridanými glyphmi.
