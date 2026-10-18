---
name: feedback-review-nulove
description: Pred implementáciou chce kritickú review specu aj plánu dotiahnutú na 0/0/0/0
metadata:
  node_type: memory
  type: feedback
  originSessionId: b3956cdb-161a-4185-8cf7-509308ed727e
  modified: 2026-10-18T20:44:11.710Z
---

Spec aj implementačný plán musia prejsť nezávislou kritickou review až na **0 critical / 0 high / 0 medium / 0 low** — opravovať a opakovať review, kým nie sú všetky nuly.
**Why:** používateľ sa výslovne pýtal, či bola spravená, a stanovil tento cieľ (2026-10-12).
**How to apply:** po napísaní specu/plánu spusti nezávislého recenzenta (čerstvý kontext), oprav nálezy, opakuj; až potom implementácia.
Platí aj pre hotový web: „kritická review celého webu“ = nezávislý recenzent, opravy, opakovať až na 0/0/0/0 (žiadal 2026-10-18). Súvisí s [[project-alfa-taekwondo-web]].
