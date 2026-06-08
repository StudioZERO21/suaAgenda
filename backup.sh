#!/bin/bash
# Backup do banco suaAgenda antes de migrations.
# Uso: bash backup.sh [etapa]   ex: bash backup.sh 1.5

set -e

ETAPA="${1:-manual}"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="BACKUPS"
DB_NAME="suaAgenda"
DB_USER="root"
DB_PASS=""

# Paths alternativos para mysqldump (Laragon / sistema)
MYSQLDUMP=""
for p in \
    "/d/laragon3/bin/mysql/mysql-8.4.3-winx64/bin/mysqldump" \
    "/d/laragon3/bin/mysql/mysql-8.0.30-winx64/bin/mysqldump" \
    "mysqldump"; do
    if command -v "$p" >/dev/null 2>&1 || [ -x "$p" ]; then
        MYSQLDUMP="$p"
        break
    fi
done

[ -z "$MYSQLDUMP" ] && { echo "❌ mysqldump não encontrado"; exit 1; }

mkdir -p "$BACKUP_DIR"

OUT="$BACKUP_DIR/db-etapa-${ETAPA}-${TIMESTAMP}.sql.gz"

if "$MYSQLDUMP" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" | gzip > "$OUT"; then
    SIZE=$(du -h "$OUT" | cut -f1)
    echo "✅ Backup criado: $OUT ($SIZE)"
else
    echo "❌ Falha no backup"
    exit 1
fi
