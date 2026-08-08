#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

# DAR-LTCMS encrypted production backup.
#
# This script intentionally keeps only a temporary PostgreSQL dump on the
# application server. The database dump, production Laravel configuration,
# and user-generated storage files are backed up into an encrypted restic
# repository stored off-server.
#
# Required backup configuration is loaded from:
#   $BACKUP_ENV_FILE
# or, by default:
#   $HOME/.config/dar-ltcms/backup.env

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
BACKUP_ENV_FILE="${BACKUP_ENV_FILE:-$HOME/.config/dar-ltcms/backup.env}"
STAGING_DIR="${BACKUP_STAGING_DIR:-$HOME/.cache/dar-ltcms-backup}"
BACKUP_HOST="${BACKUP_HOST:-darltcms-production}"
BACKUP_TAG="${BACKUP_TAG:-dar-ltcms-production}"

KEEP_DAILY="${BACKUP_KEEP_DAILY:-7}"
KEEP_WEEKLY="${BACKUP_KEEP_WEEKLY:-4}"
KEEP_MONTHLY="${BACKUP_KEEP_MONTHLY:-3}"
CHECK_PERCENT="${BACKUP_CHECK_PERCENT:-5}"
MIN_HEADROOM_MB="${BACKUP_MIN_HEADROOM_MB:-256}"

export PATH="$HOME/bin:/usr/local/bin:/usr/bin:/bin:${PATH:-}"

fail() {
    echo "[DAR-LTCMS backup] ERROR: $*" >&2
    exit 1
}

info() {
    echo "[DAR-LTCMS backup] $*"
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "$1 is required but was not found in PATH."
}

check_private_permissions() {
    local path="$1"
    local mode

    mode="$(stat -c '%a' "$path" 2>/dev/null || true)"
    case "$mode" in
        600|400) ;;
        *) fail "$path must have permissions 600 or 400 (current: ${mode:-unknown})." ;;
    esac
}

[ -f "$BACKUP_ENV_FILE" ] || fail "Backup configuration not found: $BACKUP_ENV_FILE"
check_private_permissions "$BACKUP_ENV_FILE"

# Export variables from the dedicated backup configuration without placing
# backup credentials in the Laravel .env file or repository.
set -a
# shellcheck disable=SC1090
source "$BACKUP_ENV_FILE"
set +a

: "${RESTIC_REPOSITORY:?RESTIC_REPOSITORY is required in $BACKUP_ENV_FILE}"
: "${RESTIC_PASSWORD_FILE:?RESTIC_PASSWORD_FILE is required in $BACKUP_ENV_FILE}"
: "${AWS_ACCESS_KEY_ID:?AWS_ACCESS_KEY_ID is required in $BACKUP_ENV_FILE}"
: "${AWS_SECRET_ACCESS_KEY:?AWS_SECRET_ACCESS_KEY is required in $BACKUP_ENV_FILE}"

[ -f "$RESTIC_PASSWORD_FILE" ] || fail "Restic password file not found: $RESTIC_PASSWORD_FILE"
check_private_permissions "$RESTIC_PASSWORD_FILE"

require_command php
require_command pg_dump
require_command pg_restore
require_command restic
require_command flock

mkdir -p "$STAGING_DIR"
chmod 700 "$STAGING_DIR"

# Prevent overlapping cron/manual backup runs.
exec 9>"$STAGING_DIR/backup.lock"
flock -n 9 || fail "Another production backup is already running."

cd "$PROJECT_ROOT"

laravel_config() {
    local key="$1"
    php -r '
        $key = $argv[1];
        require "vendor/autoload.php";
        $app = require "bootstrap/app.php";
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $value = config($key);
        if ($value !== null) {
            echo is_bool($value) ? ($value ? "1" : "0") : $value;
        }
    ' "$key"
}

DB_CONNECTION="${DB_CONNECTION:-$(laravel_config 'database.default')}"
[ "$DB_CONNECTION" = "pgsql" ] || fail "Production backup currently supports PostgreSQL only (configured: $DB_CONNECTION)."

DB_HOST="${DB_HOST:-$(laravel_config 'database.connections.pgsql.host')}"
DB_PORT="${DB_PORT:-$(laravel_config 'database.connections.pgsql.port')}"
DB_DATABASE="${DB_DATABASE:-$(laravel_config 'database.connections.pgsql.database')}"
DB_USERNAME="${DB_USERNAME:-$(laravel_config 'database.connections.pgsql.username')}"
DB_PASSWORD="${DB_PASSWORD:-$(laravel_config 'database.connections.pgsql.password')}"

[ -n "$DB_DATABASE" ] || fail "Could not resolve the PostgreSQL database name from Laravel configuration."
[ -n "$DB_USERNAME" ] || fail "Could not resolve the PostgreSQL username from Laravel configuration."

DB_DUMP="$STAGING_DIR/database.dump"
DB_DUMP_TMP="$STAGING_DIR/database.dump.tmp"
MANIFEST="$STAGING_DIR/backup-manifest.txt"

cleanup() {
    rm -f "$DB_DUMP_TMP" "$DB_DUMP" "$MANIFEST"
    unset PGPASSWORD DB_PASSWORD
}
trap cleanup EXIT INT TERM

export PGPASSWORD="$DB_PASSWORD"

# Optional conservative disk-space check. Only the database dump is staged
# locally; storage/app files and .env are streamed directly to the repository.
if command -v psql >/dev/null 2>&1; then
    DB_SIZE_BYTES="$(psql \
        --host="$DB_HOST" \
        --port="$DB_PORT" \
        --username="$DB_USERNAME" \
        --dbname="$DB_DATABASE" \
        --tuples-only \
        --no-align \
        --command='SELECT pg_database_size(current_database());' \
        2>/dev/null | tr -d '[:space:]' || true)"

    FREE_KB="$(df -Pk "$STAGING_DIR" | awk 'NR==2 {print $4}')"

    if [[ "$DB_SIZE_BYTES" =~ ^[0-9]+$ ]] && [[ "$FREE_KB" =~ ^[0-9]+$ ]]; then
        REQUIRED_KB=$(( (DB_SIZE_BYTES / 1024) + (MIN_HEADROOM_MB * 1024) ))
        if (( FREE_KB < REQUIRED_KB )); then
            fail "Insufficient staging space. Need about ${REQUIRED_KB} KB free; found ${FREE_KB} KB."
        fi
    fi
fi

info "Creating PostgreSQL custom-format dump for $DB_DATABASE..."
rm -f "$DB_DUMP_TMP" "$DB_DUMP"
pg_dump \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --username="$DB_USERNAME" \
    --format=custom \
    --no-owner \
    --no-privileges \
    --file="$DB_DUMP_TMP" \
    "$DB_DATABASE"

mv "$DB_DUMP_TMP" "$DB_DUMP"
pg_restore --list "$DB_DUMP" >/dev/null

unset PGPASSWORD DB_PASSWORD

GIT_COMMIT="unknown"
if command -v git >/dev/null 2>&1 && git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    GIT_COMMIT="$(git rev-parse --short HEAD 2>/dev/null || echo unknown)"
fi

cat > "$MANIFEST" <<EOF
DAR-LTCMS production backup
Created: $(date -Iseconds)
Application host: $(hostname 2>/dev/null || echo unknown)
Application commit: $GIT_COMMIT
Database: $DB_DATABASE
Database host: $DB_HOST:$DB_PORT
Database format: PostgreSQL custom dump
Laravel .env included: $([ -f "$PROJECT_ROOT/.env" ] && echo yes || echo no)
Restic tag: $BACKUP_TAG
EOF

BACKUP_TARGETS=(
    "$DB_DUMP"
    "$MANIFEST"
)

[ -f "$PROJECT_ROOT/.env" ] && BACKUP_TARGETS+=("$PROJECT_ROOT/.env")
[ -d "$PROJECT_ROOT/storage/app/private" ] && BACKUP_TARGETS+=("$PROJECT_ROOT/storage/app/private")
[ -d "$PROJECT_ROOT/storage/app/public" ] && BACKUP_TARGETS+=("$PROJECT_ROOT/storage/app/public")

info "Uploading encrypted off-site snapshot..."
restic backup \
    --host "$BACKUP_HOST" \
    --tag "$BACKUP_TAG" \
    "${BACKUP_TARGETS[@]}"

info "Applying retention: $KEEP_DAILY daily, $KEEP_WEEKLY weekly, $KEEP_MONTHLY monthly..."
restic forget \
    --host "$BACKUP_HOST" \
    --tag "$BACKUP_TAG" \
    --keep-daily "$KEEP_DAILY" \
    --keep-weekly "$KEEP_WEEKLY" \
    --keep-monthly "$KEEP_MONTHLY" \
    --prune

if [[ "$CHECK_PERCENT" =~ ^([0-9]+([.][0-9]+)?)$ ]] && [ "$CHECK_PERCENT" != "0" ]; then
    info "Checking repository metadata and a ${CHECK_PERCENT}% data sample..."
    restic check --read-data-subset="${CHECK_PERCENT}%"
else
    info "Repository data-sample check skipped (BACKUP_CHECK_PERCENT=$CHECK_PERCENT)."
fi

info "Latest production snapshot:"
restic snapshots --host "$BACKUP_HOST" --tag "$BACKUP_TAG" --latest 1

info "Backup completed successfully. Temporary local dump will now be removed."
