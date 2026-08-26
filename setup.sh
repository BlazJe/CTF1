#!/usr/bin/env bash
# Upravlja lokalno CTF okolje z docker compose.
#
# Uporaba:
#   ./setup.sh [up|down|reset]
#
#   up     (privzeto) - zgradi in zažene okolje, doda vnosa v /etc/hosts (sudo)
#   down   - ustavi vsebnike, odstrani vnosa iz /etc/hosts, podatki ostanejo
#   reset  - popolnoma počisti (vsebniki + volumni), znova zgradi in zažene,
#            ter ponovno doda vnosa v /etc/hosts
#
# Edina sprememba na gostitelju je dodajanje/odstranjevanje dveh vrstic v
# /etc/hosts, jasno označenih z markerjema spodaj - nič drugega na
# gostitelju se ne dotika (brez uporabniških računov, brez sistemskih
# nastavitev).
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"

HOSTS_START="# >>> nordvel-ctf-lab >>>"
HOSTS_END="# <<< nordvel-ctf-lab <<<"
HOSTS_FILE="/etc/hosts"

cmd="${1:-up}"

require_docker() {
  if ! command -v docker >/dev/null 2>&1; then
    echo "Napaka: docker ni nameščen ali ni v PATH." >&2
    exit 1
  fi
}

add_hosts_entries() {
  if grep -qF "$HOSTS_START" "$HOSTS_FILE" 2>/dev/null; then
    echo "==> Vnosa v /etc/hosts že obstajata, preskačem."
    return
  fi
  echo "==> Dodajam vnosa v /etc/hosts (potreben sudo)..."
  sudo tee -a "$HOSTS_FILE" >/dev/null <<EOF

$HOSTS_START
127.0.0.1 ctf.lan
127.0.0.1 score.lan
$HOSTS_END
EOF
}

remove_hosts_entries() {
  if ! grep -qF "$HOSTS_START" "$HOSTS_FILE" 2>/dev/null; then
    echo "==> V /etc/hosts ni najdenih vnosov tega laba, nič za odstraniti."
    return
  fi
  echo "==> Odstranjujem vnosa iz /etc/hosts (potreben sudo)..."
  sudo sed -i "/^${HOSTS_START//\//\\/}$/,/^${HOSTS_END//\//\\/}$/d" "$HOSTS_FILE"
}

wait_ready() {
  echo "==> Čakam, da se storitve zaženejo..."
  local ready=0
  for _ in $(seq 1 40); do
    if curl -sf http://127.0.0.1:8080/robots.txt >/dev/null 2>&1 \
       && curl -sf http://127.0.0.1:8000/ >/dev/null 2>&1; then
      ready=1
      break
    fi
    sleep 2
  done
  if [ "$ready" -ne 1 ]; then
    echo "OPOZORILO: storitvi po 80s še nista odgovorili - preveri 'docker compose logs'." >&2
  fi
}

print_info() {
  cat <<'EOF'

================================================================
 CTF okolje je pripravljeno.
================================================================

Naslovi:
  Ciljna aplikacija (Nordvel d.o.o.):  http://ctf.lan:8080   (ali http://127.0.0.1:8080)
  Scoreboard (oddaja zastavic):        http://score.lan:8000 (ali http://127.0.0.1:8000)

Odpri scoreboard, vpiši ime ekipe in začni s prvo nalogo.

Ustavitev (obdrži podatke, počisti /etc/hosts):     ./setup.sh down
Popoln reset (izbriše vse podatke in znova zažene): ./setup.sh reset

================================================================
EOF
}

case "$cmd" in
  up)
    require_docker
    echo "==> Gradim in zaganjam CTF okolje (docker compose)..."
    docker compose up -d --build
    wait_ready
    add_hosts_entries
    print_info
    ;;

  down)
    require_docker
    echo "==> Ustavljam vsebnike (podatki ostanejo - baza, uploads, scoreboard)..."
    docker compose down
    remove_hosts_entries
    echo "==> Okolje ustavljeno, /etc/hosts počiščen."
    echo "    Za ponoven zagon: ./setup.sh up"
    echo "    Za popoln reset:  ./setup.sh reset"
    ;;

  reset)
    require_docker
    echo "==> Ustavljam in brišem vsebnike ter volumne (baza, uploads, scoreboard)..."
    docker compose down -v
    echo "==> Ponovno gradim in zaganjam okolje..."
    docker compose up -d --build
    wait_ready
    add_hosts_entries
    echo "==> Reset končan. Okolje je v enakem stanju kot ob prvi postavitvi."
    print_info
    ;;

  *)
    echo "Uporaba: $0 [up|down|reset]" >&2
    exit 1
    ;;
esac
