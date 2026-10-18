# Testy v prehliadači (end-to-end)

Skripty ovládajú Chrome bez okna cez DevTools protokol (Node 22+, bez balíčkov) — skutočné kliky myšou, písanie,
pretiahnutie súborov. Spúšťaj ich **po každej zmene adminu** (CSS/JS), nielen `php tests/run.php`:
editor Quill raz neviditeľne prekryl tlačidlá a PHP testy to nemohli zachytiť.

## 1. Prihlasovacie cookies
```bash
B=http://localhost:8000/admin            # alebo https://<doména>/admin
J=cookies.jar
T=$(curl -s -c $J $B/login.php | grep -o 'name="csrf" value="[^"]*' | head -1 | sed 's/.*value="//')
curl -s -b $J -c $J -o /dev/null --data-urlencode "csrf=$T" --data-urlencode "user=<e-mail>" --data-urlencode 'password=<heslo>' $B/login.php
sed 's/^#HttpOnly_//' $J | grep -v '^#' | awk 'NF>=7{print $6"="$7}' > cookies.txt
```
Súbory s cookies a heslá nikdy nedávaj do repa.

## 2. Skripty
| Skript | Čo overí |
|---|---|
| `e2e.mjs <web> <cookies.txt> <foto.jpg>` | celý postup: koncept → nie je na webe (zoznam, úvod, 404) → filter → Enter nezverejní → zverejniť s fotkou → skryť → náhľad → album koncept → fotka → zverejniť; na konci testovacie dáta zmaže |
| `drag-drop.mjs <url> <cookies.txt> <selektor> <súbor1;súbor2> <kontrolný JS>` | pretiahnutie súborov myšou na prvok (album, editor, titulná fotka) |
| `probe-buttons.mjs <url> <cookies.txt> [šírka]` | či tlačidlá formulára nič neprekrýva (`elementFromPoint`) a či je formulár platný |

Príklad: `node tools/e2e/e2e.mjs http://localhost:8000 cookies.txt C:\\cesta\\foto.jpg` — každý riadok musí byť `OK`.
Cesty k súborom pre Chrome píš vo formáte Windows (`cygpath -w`).
