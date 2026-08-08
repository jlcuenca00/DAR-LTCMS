# DAR-LTCMS Backup and Recovery Procedure

DAR-LTCMS uses two backup modes:

1. **Manual/local snapshot** for development or one-off maintenance work.
2. **Encrypted off-site production backup** for the deployed DAR-LTCMS server.

Production backups must never be stored under the public web root.

---

## 1. Manual/local snapshot

From the project root:

```bash
bash scripts/backup_dar_ltcms.sh
```

This creates a timestamped folder under `storage/backups/` containing:

- PostgreSQL custom-format dump
- private uploaded files, when present
- manifest
- SHA-256 checksums

`storage/backups/` and database dump files are ignored by Git and must never be committed.

---

## 2. Production backup design

Production uses:

```bash
bash scripts/backup_dar_ltcms_production.sh
```

The production script:

1. reads the active Laravel PostgreSQL configuration;
2. creates one temporary PostgreSQL custom-format dump outside the web root;
3. validates the dump with `pg_restore --list`;
4. backs up the dump, production `.env`, `storage/app/private`, and `storage/app/public`;
5. encrypts and uploads the snapshot to an off-site restic repository;
6. keeps 7 daily, 4 weekly, and 3 monthly snapshots by default;
7. prunes expired snapshots;
8. checks repository metadata plus a 5% data sample by default; and
9. removes the temporary local database dump after completion.

Application source code, `vendor/`, `node_modules/`, `public/build/`, and the `public/storage` symlink are not duplicated in the production backup.

---

## 3. Recommended off-site destination

Use a **private Backblaze B2 bucket** through its S3-compatible HTTPS endpoint.

Recommended repository form:

```text
s3:https://s3.<region>.backblazeb2.com/<private-bucket-name>/dar-ltcms
```

Use a dedicated application key restricted to the backup bucket. The key must support the object read/write/delete/list operations required by restic retention and verification.

For Backblaze B2 lifecycle settings, use the provider option that keeps only the latest file version so hidden/old object versions removed by restic do not continue consuming storage indefinitely.

---

## 4. Production secret files

Create a private backup configuration directory on the production server:

```bash
mkdir -p ~/.config/dar-ltcms
chmod 700 ~/.config/dar-ltcms
```

Copy the repository template:

```bash
cp scripts/backup.env.example ~/.config/dar-ltcms/backup.env
chmod 600 ~/.config/dar-ltcms/backup.env
```

Edit it with the real private bucket endpoint and application key:

```bash
nano ~/.config/dar-ltcms/backup.env
```

Create a strong restic encryption password file:

```bash
nano ~/.config/dar-ltcms/restic-password
chmod 600 ~/.config/dar-ltcms/restic-password
```

The restic password must also be stored securely **outside the production server**. Losing the password means the encrypted backup repository cannot be recovered.

Do not commit `backup.env`, the restic password, or any application keys to GitHub.

---

## 5. Initialize the off-site repository once

Load the private backup configuration:

```bash
set -a
source ~/.config/dar-ltcms/backup.env
set +a
```

Then initialize the repository:

```bash
restic init
```

Verify access:

```bash
restic snapshots
restic check
```

Initialization must only be performed once for a new empty repository.

---

## 6. First production backup

From the Laravel project root:

```bash
cd ~/htdocs/darltcms.me
bash scripts/backup_dar_ltcms_production.sh
```

A successful run must end with a latest snapshot listing and:

```text
Backup completed successfully. Temporary local dump will now be removed.
```

Then verify that no dump was left behind:

```bash
ls -lah ~/.cache/dar-ltcms-backup
```

Only the lock file should remain when the backup is idle.

---

## 7. Schedule daily production backups

Use the `darltcms` account's crontab so the job does not require root.

Open the user crontab:

```bash
crontab -e
```

Recommended daily schedule:

```cron
30 2 * * * cd /home/darltcms/htdocs/darltcms.me && /usr/bin/bash scripts/backup_dar_ltcms_production.sh >> /home/darltcms/.cache/dar-ltcms-backup/backup.log 2>&1
```

This runs at **2:30 AM server local time**. Confirm the server timezone before relying on this schedule.

Check the installed schedule:

```bash
crontab -l
```

The backup script uses a file lock so a second run will fail safely instead of overlapping an existing run.

---

## 8. Restore into a test environment first

Never test restoration against the active production database.

Create a temporary restore directory:

```bash
mkdir -p ~/dar-ltcms-restore-test
chmod 700 ~/dar-ltcms-restore-test
```

Load backup credentials:

```bash
set -a
source ~/.config/dar-ltcms/backup.env
set +a
```

List snapshots:

```bash
restic snapshots --host darltcms-production --tag dar-ltcms-production
```

Restore the selected snapshot into the temporary directory:

```bash
restic restore <snapshot-id> --target ~/dar-ltcms-restore-test
```

Locate the restored `database.dump`:

```bash
find ~/dar-ltcms-restore-test -name database.dump -type f -print
```

Validate it:

```bash
pg_restore --list /path/to/restored/database.dump > /dev/null
```

Create a separate PostgreSQL test database and restore into it:

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

Use a separate test copy of the Laravel project and point its database configuration to `dar_iland_restore_test`.

Verify at minimum:

- users and role assignments
- landowner records
- parcel records
- landholdings
- applications and application parties
- uploaded document references/files
- workflow history
- final decisions
- generated clearance records
- notifications
- audit logs
- profile photos or other public-storage user uploads

Do not send email, mutate production records, or run the restored test copy against the live production database.

---

## 9. Production restoration rule

A production restore requires all of the following before proceeding:

1. authorized approval;
2. a verified current backup;
3. a tested restore of the selected snapshot;
4. a defined maintenance window;
5. a rollback plan; and
6. confirmation that the restore will not overwrite a newer valid production state by mistake.

Restoring DAR-LTCMS data restores administrative processing and monitoring records only. It does not execute or finalize land ownership transfer or registry mutation.
