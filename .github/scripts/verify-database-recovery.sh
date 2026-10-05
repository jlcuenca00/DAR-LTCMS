#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

# CI-only disposable PostgreSQL databases inside the job's service container.
[ "${CI:-}" = true ] && [ "${APP_ENV:-}" = testing ] || {
    echo "Recovery checks require CI=true and APP_ENV=testing." >&2
    exit 1
}
: "${POSTGRES_CONTAINER:?The isolated PostgreSQL CI service container is required}"
: "${GITHUB_RUN_ID:?A CI run ID is required}"
[[ "$GITHUB_RUN_ID" =~ ^[0-9]+$ ]] || exit 1

source_db="darltcms_recovery_source_$GITHUB_RUN_ID"
restore_db="darltcms_recovery_restore_$GITHUB_RUN_ID"
recovery_tmp="$(mktemp -d)"
cleanup() {
    docker exec "$POSTGRES_CONTAINER" dropdb -U postgres --if-exists "$restore_db" >/dev/null 2>&1 || true
    docker exec "$POSTGRES_CONTAINER" dropdb -U postgres --if-exists "$source_db" >/dev/null 2>&1 || true
    rm -rf "$recovery_tmp"
}
trap cleanup EXIT

docker exec "$POSTGRES_CONTAINER" createdb -U postgres -T template0 "$source_db"
docker exec "$POSTGRES_CONTAINER" createdb -U postgres -T template0 "$restore_db"

# Explicit testing connection; ignore URLs and libpq settings inherited from the runner.
unset DB_URL PGSERVICE PGSERVICEFILE PGHOST PGPORT PGDATABASE PGUSER PGPASSWORD
export DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432 DB_USERNAME=postgres DB_PASSWORD=postgres
export DB_DATABASE="$source_db"

php artisan migrate --force
php artisan migrate:reset --force
php .github/scripts/database-recovery-fixture.php empty
php artisan migrate --force
php .github/scripts/database-recovery-fixture.php seed
php .github/scripts/database-recovery-fixture.php fingerprint > "$recovery_tmp/before.json"

# Container tools match PostgreSQL 18, avoiding runner-client version mismatches.
docker exec "$POSTGRES_CONTAINER" pg_dump -U postgres --format=custom --no-owner --no-privileges "$source_db" > "$recovery_tmp/database.dump"
docker exec -i "$POSTGRES_CONTAINER" pg_restore -U postgres --dbname="$restore_db" --exit-on-error --single-transaction --no-owner --no-privileges < "$recovery_tmp/database.dump"

export DB_DATABASE="$restore_db"
php .github/scripts/database-recovery-fixture.php fingerprint > "$recovery_tmp/after.json"
if ! cmp "$recovery_tmp/before.json" "$recovery_tmp/after.json"; then
    # Synthetic fingerprints/catalog definitions only; no record values or secrets.
    diff -u "$recovery_tmp/before.json" "$recovery_tmp/after.json" || true
    exit 1
fi
echo "Database recovery verified: full rollback/remigration, restored records, constraints, and audit protection."
