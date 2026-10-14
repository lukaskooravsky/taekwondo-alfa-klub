---
name: reference-local-php
description: Lokálne PHP pre CMS admin — prenosné PHP 8.3 v C:\Users\lukas\tools\php83 (winget zlyhal na UAC)
metadata:
  type: reference
---

Lokálne PHP na vývoj/testy CMS adminu: **prenosné PHP 8.3.35** (ZIP z windows.php.net, SHA256 overené)
v `C:\Users\lukas\tools\php83\` s vlastným `php.ini` (gd, fileinfo, mbstring, exif; upload 8M/post 10M).
V PATH cez `~/.bashrc` (`/c/Users/lukas/tools/php83`).

`winget install PHP.PHP.8.3` zlyhal (2026-10-14) — závislosť VC++ redist chce admin práva (UAC), nikto nepotvrdil.
VC++ runtime (vcruntime140.dll) v systéme už je, takže ZIP verzia beží bez inštalácie.
Task 1 Step 1 v pláne treba podľa toho upraviť. Súvisí s [[project-alfa-taekwondo-web]].
