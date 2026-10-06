# DAR-LTCMS Backup and Recovery Procedure

This document describes how to back up and recover the database, configuration, and uploaded files. Check current logs and snapshots, and verify recovery in an isolated environment before restoring production.

DAR-LTCMS supports two backup approaches:

1. **Manual/local snapshot** for development, maintenance, or pre-release protection.
2. **Encrypted off-site production backup** using the provided production backup script and a private restic repository.

Production backups must never be exposed through the public web root or committed to GitHub.

## 1. Manual/local snapshot

From the project root:

```bash
bash scripts/backup_dar_ltcms.sh
```

The local helper uses shell settings/defaults, not Laravel's resolved connection. Confirm `DB_HOST`, `DB_PORT`, `DB_DATABASE` and `DB_USERNAME` before using it; it defaults to `dar_iland` and `postgres`. It writes a database dump, private-file archive when present, a manifest and optional checksums under `storage/backups/`. It does not include the production `.env` or legacy `storage/app/public` files. Use the effective-connection off-site script for production.

`storage/backups/` and database dump files are excluded from deployment/source-control use and must never be committed.

For the final `v1.0.0` pre-release backup, follow `docs/RELEASE_PREPARATION.md` even if another backup routine is also configured.

## 2. Supported off-site production design

The repository provides:

```bash
bash scripts/backup_dar_ltcms_production.sh
```

When correctly configured, the production script is designed to:

1. read the active Laravel PostgreSQL configuration;
2. create a temporary PostgreSQL custom-format dump outside the web root;
3. validate the dump with `pg_restore --list`;
4. include the dump, production `.env`, `storage/app/private`, and `storage/app/public` in the protected snapshot;
5. encrypt/upload the snapshot through restic;
6. apply configured retention;
7. check repository metadata and the configured data sample (5% by default); and
8. remove the temporary local database dump after completion.

Application source, `vendor/`, `node_modules/`, generated frontend assets, and a public storage symlink do not need to be duplicated as production data backups.

## 3. Off-site destination example

A private S3-compatible object-storage bucket, such as Backblaze B2, may be used with restic.

Example repository form:

```text
s3:https://s3.<region>.backblazeb2.com/<private-bucket-name>/dar-ltcms
```

Use a dedicated restricted application key. Backup credentials, repository passwords, and bucket details are production secrets and must not be committed.

## 4. Production secret files

On the production server, a private configuration directory may be created as:

```bash
mkdir -p ~/.config/dar-ltcms
chmod 700 ~/.config/dar-ltcms
cp scripts/backup.env.example ~/.config/dar-ltcms/backup.env
chmod 600 ~/.config/dar-ltcms/backup.env
```

Store the restic encryption password in a separate protected file, for example:

```bash
nano ~/.config/dar-ltcms/restic-password
chmod 600 ~/.config/dar-ltcms/restic-password
```

The encryption password must also be stored securely outside the production server. Losing it can make encrypted backups unrecoverable.

## 5. Initialize and verify the off-site repository

Only after the real production backup configuration has been securely prepared:

```bash
set -a
source ~/.config/dar-ltcms/backup.env
set +a
restic init
restic snapshots
restic check
```

`restic init` is performed only for a new empty repository.

## 6. First production backup verification

From the Laravel project root:

```bash
cd /home/darltcms/htdocs/darltcms.me
bash scripts/backup_dar_ltcms_production.sh
```

Do not consider off-site backups operational until a real backup completes successfully and its snapshot can be listed/checked.

Also confirm temporary plaintext dumps are not left behind after a successful production run.

## 7. Scheduling

The verified production server uses UTC. Its nightly schedule runs at 18:30 UTC, which is 2:30 AM Philippine time the next day:

```cron
30 18 * * * /usr/bin/bash /home/darltcms/htdocs/darltcms.me/scripts/run_production_backup_with_alert.sh
```

Keep exactly one backup cron entry, running as `darltcms`. Preserve other cron tasks. Recalculate the schedule if the server timezone changes.

The wrapper writes to `darltcms-backup` logs and attempts a failure-only email using the application's mail service and the private recipient file. Follow [alert setup](BACKUP_ALERT_SETUP.md) to configure and test inbox delivery.

A failure email cannot detect a stopped scheduler, server outage or unavailable mail service. Current snapshots, periodic restore tests and optional independent missed-backup monitoring remain important.

## 8. Restore into a test environment first

Never test restoration against the active production database.

Create a separate restore location and load the backup credentials:

```bash
umask 077
RESTORE_TEST_DIR=$(mktemp -d "$HOME/dar-ltcms-restore-test.XXXXXX")
export PATH="$HOME/bin:$PATH"
set -a
source ~/.config/dar-ltcms/backup.env
set +a
```

List snapshots and restore the selected snapshot to the temporary target:

```bash
restic snapshots --host darltcms-production --tag dar-ltcms-production
restic restore <snapshot-id> --target "$RESTORE_TEST_DIR" --verify
```

Use a fresh target for verification: some Restic versions skip files already restored and can report zero files verified. Require a successful restore and meaningful verification result.

Validate the restored database dump:

```bash
pg_restore --list /path/to/restored/database.dump > /dev/null
```

Restore into a newly created, separate PostgreSQL test database, never the live database. Confirm the test host/port and credentials; the example assumes a local test PostgreSQL service and refuses restore on the first error:

```bash
createdb -U postgres dar_iland_restore_test &&
pg_restore -U postgres \
  --dbname=dar_iland_restore_test \
  --exit-on-error \
  --no-owner \
  --no-privileges \
  /path/to/restored/database.dump
```

Point a separate test copy of DAR-LTCMS to `dar_iland_restore_test`.

Verify at minimum:

- users and role assignments
- Landowner records
- Parcel records
- Landholdings
- Clearance Applications and parties
- protected uploads/document references
- workflow/final-state data
- generated clearance records
- notifications
- Audit Logs
- private/reference/profile files included by the selected backup

Do not send production email, mutate live records, or point the restore test copy at the live database. After documenting successful checks, stop any temporary database instance and remove only the private test directories/databases created for this exercise; restored settings and records are sensitive.

## 9. Production restoration rule

A production restore requires:

1. authorized approval;
2. a verified current backup;
3. a tested restore of the selected snapshot;
4. a defined maintenance window;
5. a rollback plan;
6. confirmation of the exact target database/snapshot; and
7. confirmation that a newer valid production state will not be overwritten accidentally.

For a code/UI-only regression, prefer reverting the code/deployment rather than restoring the database.

Restoring DAR-LTCMS restores administrative processing/monitoring records only. It does not execute or finalize legal land ownership transfer or registry mutation.
