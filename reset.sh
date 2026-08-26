#!/usr/bin/env bash
# Bližnjica za popoln reset okolja - enakovredno "./setup.sh reset".
# Podre vsebnike skupaj z volumni, znova zgradi in zažene ter obnovi
# vnosa v /etc/hosts (isti markerji kot v setup.sh, brez drugih posegov
# na gostitelju).
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"
exec ./setup.sh reset
