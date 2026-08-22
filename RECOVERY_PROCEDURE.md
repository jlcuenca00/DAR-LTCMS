# DAR-LTCMS Backup and Recovery Procedure

This document describes supported backup/recovery procedures for DAR-LTCMS. It does **not** by itself prove that an off-site backup job is currently configured on the live server; production configuration and successful restore testing must be verified operationally.

DAR-LTCMS supports two backup approaches:

1. **Manual/local snapshot** for development, maintenance, or pre-release protection.
2. **Encrypted off-site production backup** using the provided production backup script and a private restic repository.

Production backups must never be exposed through the public web root or committed to GitHub.

## 1. Manual/local snapshot

From the project root:

```bash
bash scripts/backup_dar_ltcms.sh
```

The local backup script creates a timestamped backup under `storage/backups/` containing the data handled by the script, such as the PostgreSQL dump/private files/manifest/checksums where available.

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
7. verify repository data; and
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

If automated daily production backups are approved and configured, use the `darltcms` deployment account rather than requiring root.

Example cron schedule:

```cron
30 2 * * * cd /home/darltcms/htdocs/darltcms.me && /usr/bin/bash scripts/backup_dar_ltcms_production.sh >> /home/darltcms/.cache/dar-ltcms-backup/backup.log 2>&1
```

Confirm the server timezone, log location, credentials, retention, and successful scheduled execution. A configured cron entry is not sufficient evidence of recoverability by itself.

## 8. Restore into a test environment first

Never test restoration against the active production database.

Create a separate restore location and load the backup credentials:

```bash
mkdir -p ~/dar-ltcms-restore-test
chmod 700 ~/dar-ltcms-restore-test
set -a
source ~/.config/dar-ltcms/backup.env
set +a
```

List snapshots and restore the selected snapshot to the temporary target:

```bash
restic snapshots --host darltcms-production --tag dar-ltcms-production
restic restore <snapshot-id> --target ~/dar-ltcms-restore-test
```

Validate the restored database dump:

```bash
pg_restore --list /path/to/restored/database.dump > /dev/null
```

Restore into a separate PostgreSQL test database, never the live database:

```bash
createdb -U postgres dar_iland_restore_test
pg_restore -U postgres \
  --dbname=dar_iland_restore_test \
  --clean \
  --if-exists \
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

Do not send production email, mutate live records, or point the restore test copy at the live database.

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
