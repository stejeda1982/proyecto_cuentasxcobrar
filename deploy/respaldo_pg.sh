#!/bin/sh
# T0.8 — Respaldo diario de PostgreSQL (RNF-DIS-03).
# pg_dump custom format + verificación pg_restore --list + retención 14 días.
set -eu

DESTINO=/backups
FECHA=$(date +%Y%m%d_%H%M)
ARCHIVO="$DESTINO/cxc_$FECHA.dump"

echo "[$(date -Iseconds)] Respaldo iniciado"
pg_dump -h db -U "${DB_USERNAME:-cxc}" -d "${DB_DATABASE:-cxc}" -Fc -f "$ARCHIVO"

# Verificación: el respaldo debe poder listarse (prueba de integridad).
pg_restore --list "$ARCHIVO" > /dev/null

echo "[$(date -Iseconds)] Respaldo OK: $ARCHIVO ($(du -h "$ARCHIVO" | cut -f1))"

# Retención: conservar los últimos 14 días.
find "$DESTINO" -name 'cxc_*.dump' -mtime +14 -delete
echo "[$(date -Iseconds)] Retención aplicada (14 días)"
