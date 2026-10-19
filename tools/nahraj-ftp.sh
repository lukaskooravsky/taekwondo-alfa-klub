#!/usr/bin/env bash
# Nahrá na ukážku alfa.oravsky.sk (Webglobe FTP) súbory zmenené od posledného nahratia.
#   bash tools/nahraj-ftp.sh              → zmeny od značky git "ftp-alfa" po HEAD (len commitnuté)
#   bash tools/nahraj-ftp.sh a.php css/x  → len tieto súbory
# Heslo si vypýta (nezobrazuje sa), uloží ho len do dočasného súboru, ktorý hneď zmaže.
# Po úspešnom nahratí presunie značku "ftp-alfa" na HEAD. Zmazané súbory treba zmazať na FTP ručne.
set -u
cd "$(dirname "$0")/.." || exit 1

FTP_USER="dusan@oravsky.sk"
FTP_HOST="ftp.nameserver.sk"               # = ftp.oravsky.sk; certifikát je na *.nameserver.sk
FTP_IP="195.181.251.39"
FTP_DIR="public_html/_sub/alfa"
TAG="ftp-alfa"

if [ $# -gt 0 ]; then
  FILES=("$@")
else
  if ! git rev-parse -q --verify "$TAG" >/dev/null; then
    echo "Chýba značka $TAG (posledné nahratie). Zadaj súbory ručne: bash tools/nahraj-ftp.sh súbor…"; exit 1
  fi
  # na hosting nepatrí: testy, nástroje, dokumentácia
  mapfile -t FILES < <(git diff --name-only --diff-filter=AMR "$TAG" HEAD -- . \
    ':!tests/' ':!tools/' ':!docs/' ':!*.md' ':!.gitignore' ':!.gitattributes')
  mapfile -t GONE < <(git diff --name-only --diff-filter=D "$TAG" HEAD -- . ':!tests/' ':!tools/' ':!docs/' ':!*.md')
  [ ${#GONE[@]} -gt 0 ] && printf 'Pozn.: zmazané v gite (na FTP ostávajú, nevadia): %s\n' "${GONE[@]}"
fi
[ -n "$(git status --porcelain -uno)" ] && echo "Pozor: máš necommitnuté zmeny — nahrávajú sa súbory tak, ako sú na disku."

if [ ${#FILES[@]} -eq 0 ]; then echo "Nič nové na nahratie."; read -rp "Enter zavrie okno."; exit 0; fi
echo "Nahrám na $FTP_DIR:"; printf '  %s\n' "${FILES[@]}"
read -rsp "FTP heslo pre $FTP_USER: " P; echo

NETRC=$(mktemp); trap 'rm -f "$NETRC"' EXIT
chmod 600 "$NETRC"
printf 'machine %s login %s password %s\n' "$FTP_HOST" "$FTP_USER" "$P" > "$NETRC"
unset P
NETRC_ARG=$(cygpath -w "$NETRC" 2>/dev/null || echo "$NETRC")   # curl.exe vo Windows chce windowsovú cestu

FAIL=0
for f in "${FILES[@]}"; do
  if curl -sS --ssl-reqd --ftp-create-dirs --resolve "$FTP_HOST:21:$FTP_IP" --netrc-file "$NETRC_ARG" \
       -T "$f" "ftp://$FTP_HOST/$FTP_DIR/$f"; then
    echo "OK     $f"
  else
    echo "CHYBA  $f"; FAIL=1
  fi
done
rm -f "$NETRC"

if [ $FAIL -eq 0 ] && [ $# -eq 0 ]; then
  git tag -f "$TAG" HEAD >/dev/null && echo "Hotovo — značka $TAG posunutá na $(git rev-parse --short HEAD)."
elif [ $FAIL -ne 0 ]; then
  echo "Niečo zlyhalo (zlé heslo? sieť?) — značka $TAG ostáva, skús znova."
fi
read -rp "Enter zavrie okno."
