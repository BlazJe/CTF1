#!/usr/bin/env bash
# Rešitve so v repozitoriju samo v šifrirani obliki (WALKTHROUGH.md.enc),
# da jih udeleženec ne more prebrati, tudi če pride do repozitorija.
#
#   ./scripts/walkthrough.sh open    odšifrira v WALKTHROUGH.md (v .gitignore)
#   ./scripts/walkthrough.sh show    izpiše na zaslon, brez zapisa na disk
#   ./scripts/walkthrough.sh lock    znova zašifrira WALKTHROUGH.md in ga pobriše
#
# Uporablja openssl (AES-256-CBC, PBKDF2). Geslo pozna mentor.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

PLAIN=WALKTHROUGH.md
ENC=WALKTHROUGH.md.enc
CIPHER=(-aes-256-cbc -pbkdf2 -iter 200000 -salt)

need_openssl() {
  command -v openssl >/dev/null 2>&1 || {
    echo "Napaka: openssl ni nameščen." >&2
    exit 1
  }
}

case "${1:-open}" in
  open)
    need_openssl
    [ -f "$ENC" ] || { echo "Napaka: $ENC ne obstaja." >&2; exit 1; }
    openssl enc -d "${CIPHER[@]}" -in "$ENC" -out "$PLAIN"
    echo "Odšifrirano v $PLAIN (datoteka je v .gitignore, ne bo objavljena)."
    ;;

  show)
    need_openssl
    [ -f "$ENC" ] || { echo "Napaka: $ENC ne obstaja." >&2; exit 1; }
    openssl enc -d "${CIPHER[@]}" -in "$ENC"
    ;;

  lock)
    need_openssl
    [ -f "$PLAIN" ] || { echo "Napaka: $PLAIN ne obstaja." >&2; exit 1; }
    openssl enc "${CIPHER[@]}" -in "$PLAIN" -out "$ENC"
    shred -u "$PLAIN" 2>/dev/null || rm -f "$PLAIN"
    echo "Zašifrirano v $ENC, čistopis pobrisan."
    ;;

  *)
    echo "Uporaba: $0 [open|show|lock]" >&2
    exit 1
    ;;
esac
