#!/usr/bin/env bash
set -euo pipefail

# DAR-LTCMS backup helper for Git Bash/Linux.
# Optional environment overrides:
#   DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, PGPASSWORD, BACKUP_DIR

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-dar_iland}"
DB_USERNAME="${DB_USERNAME:-postgres}"
BACKUP_DIR="${BACKUP_DIR:-storage/backups}"
STAMP="$(date +%Y%m%d_%H%M%S)"
DEST="${BACKUP_DIR}/dar_ltcms_${STAMP}"

command -v pg_dump >/dev/null 2>&1 || {
  echo "pg_dump was not found. Install PostgreSQL client tools or add them to PATH." >&2
  exit 1
}

mkdir -p "$DEST"

pg_dump \
  --host="$DB_HOST" \
  --port="$DB_PORT" \
  --username="$DB_USERNAME" \
  --format=custom \
  --no-owner \
  --no-privileges \
  --file="$DEST/database.dump" \
  "$DB_DATABASE"

if [ -d storage/app/private ]; then
  tar -czf "$DEST/private-files.tar.gz" -C storage/app private
fi

cat > "$DEST/manifest.txt" <<EOF
DAR-LTCMS backup
Created: $(date -Iseconds)
Database: $DB_DATABASE
Database host: $DB_HOST:$DB_PORT
Database dump: database.dump
Private files: private-files.tar.gz
EOF

if command -v sha256sum >/dev/null 2>&1; then
  (cd "$DEST" && sha256sum database.dump private-files.tar.gz 2>/dev/null > SHA256SUMS || sha256sum database.dump > SHA256SUMS)
fi

echo "Backup created: $DEST"
echo "Store a copy outside the application server and test restoration before turnover."
