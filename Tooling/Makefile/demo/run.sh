#!/usr/bin/env bash
#
# Ukázka Makefile pro PHP projekt v Dockeru.
#
# Spuštění:  bash run.sh
#
# Cíle, které sahají na Docker, se spouštějí přes `make -n` (--just-print).
# Make jen vypíše, co by provedl, a nic nespustí — takže je vidět skutečný
# příkaz, aniž bys musel mít projekt nastartovaný.

set -euo pipefail
cd "$(dirname "$0")"

hr() { printf '\n\033[1m%s\033[0m\n\n' "$1"; }

hr "1. make help — přehled, který se udržuje sám"
make help

hr "2. make test — co se doopravdy spustí"
make -n test

hr "3. make test ARGS=… — předání argumentů"
make -n test ARGS="--filter=OrderTest"

hr "4. make composer CMD=… — libovolný composer příkaz"
make -n composer CMD="require doctrine/orm"

hr "5. make check — cíl složený z jiných cílů"
make -n check

hr "6. Rozdíl lokálně vs. v CI (proměnná CI přidá -T)"
echo "   lokálně:"
make -n test | sed 's/^/     /'
echo "   v CI:"
CI=1 make -n test | sed 's/^/     /'

hr "7. make init — celý start projektu jedním příkazem"
make -n init

printf '\n\033[1mHotovo.\033[0m Žádný z příkazů se neprovedl — vše přes make -n.\n'
