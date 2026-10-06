#!/usr/bin/env bash
# Cron entry point: preserve backup failure even if logging or email fails.
set -uo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
export PATH="$HOME/bin:/usr/local/bin:/usr/bin:/bin:${PATH:-}"

bash "$SCRIPT_DIR/backup_dar_ltcms_production.sh" 2>&1 | logger -t darltcms-backup
RESULTS=("${PIPESTATUS[@]}")
BACKUP_STATUS=${RESULTS[0]}
LOGGER_STATUS=${RESULTS[1]}

if [ "$BACKUP_STATUS" -ne 0 ]; then
    echo "[DAR-LTCMS backup] Backup failed (exit $BACKUP_STATUS); attempting email alert." >&2
    if ! php "$SCRIPT_DIR/send_production_backup_alert.php"; then
        echo "[DAR-LTCMS backup] ALERT DELIVERY FAILED; inspect backup and mail service." >&2
        logger -t darltcms-backup "ALERT DELIVERY FAILED; inspect backup and mail service." || true
    fi
    exit "$BACKUP_STATUS"
fi

if [ "$LOGGER_STATUS" -ne 0 ]; then
    echo "[DAR-LTCMS backup] Backup succeeded, but logging failed." >&2
    exit "$LOGGER_STATUS"
fi
