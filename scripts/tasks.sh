#!/usr/bin/env bash
# Urejanje nalog, ki so v repozitoriju shranjene zakodirane.
#
#   ./scripts/tasks.sh show          izpise naloge v berljivi obliki (JSON)
#   ./scripts/tasks.sh edit          odpre naloge v urejevalniku in shrani nazaj
#   ./scripts/tasks.sh export FILE   zapise berljiv JSON v datoteko
#   ./scripts/tasks.sh import FILE   prebere JSON in ga zakodira nazaj
#
# Zakodiranje ni sifriranje - je zascita pred tem, da bi udelezenec resitve
# prebral kar iz repozitorija ali jih nasel z iskanjem po GitHubu.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."
BLOB=secrets/tasks.b64

decode() { base64 -d < "$BLOB" | python3 -m json.tool --no-ensure-ascii; }
encode() { python3 -c "
import json,sys,base64
data = json.load(open(sys.argv[1], encoding='utf-8'))
if not isinstance(data, list) or len(data) != 10:
    raise SystemExit('Pricakoval sem seznam desetih nalog.')
sys.stdout.write(base64.b64encode(json.dumps(data, ensure_ascii=False).encode()).decode() + '\n')
" "$1" > "$BLOB"; }

case "${1:-show}" in
  show)
    decode
    ;;
  export)
    [ $# -ge 2 ] || { echo "Uporaba: $0 export DATOTEKA" >&2; exit 1; }
    decode > "$2"
    echo "Zapisano v $2"
    ;;
  import)
    [ $# -ge 2 ] || { echo "Uporaba: $0 import DATOTEKA" >&2; exit 1; }
    encode "$2"
    echo "Naloge zakodirane v $BLOB"
    ;;
  edit)
    tmp=$(mktemp --suffix=.json)
    trap 'rm -f "$tmp"' EXIT
    decode > "$tmp"
    "${EDITOR:-nano}" "$tmp"
    encode "$tmp"
    echo "Naloge posodobljene. Za uveljavitev pozeni ./setup.sh reset"
    ;;
  *)
    echo "Uporaba: $0 [show|edit|export DATOTEKA|import DATOTEKA]" >&2
    exit 1
    ;;
esac
