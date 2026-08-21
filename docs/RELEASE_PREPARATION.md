# DAR-LTCMS Final Release Preparation

This checklist is for the DAR Negros Oriental Provincial Office deployment of DAR-LTCMS.

DAR-LTCMS is a clearance generation, processing, records-management, and monitoring system. Releasing or approving a clearance in this system does **not** automatically transfer land ownership and does **not** alter official registry ownership records.

## 1. Before the final release

Do these checks on the production server before creating the `v1.0.0` release.

### A. Create a database backup

From the DAR-LTCMS project folder on the production server:

```bash
cd /home/darltcms/htdocs/darltcms.me
mkdir -p storage/backups
```

Create a PostgreSQL custom-format backup. Replace `<production_db_user>` with the database user configured on the server. Do **not** put the database password directly in the command or commit it to GitHub.

```bash
pg_dump -Fc \
  -h 127.0.0.1 \
  -U <production_db_user> \
  -d dar_iland \
  -f "storage/backups/dar_iland_pre_v1_$(date +%Y%m%d_%H%M%S).dump"
```

Confirm that the file exists and is not empty:

```bash
ls -lh storage/backups/
```

Optionally inspect the backup catalog without restoring it:

```bash
pg_restore -l storage/backups/<backup-file>.dump | head
```

### B. Back up uploaded/private files

The deployment intentionally does not overwrite these folders:

- `storage/app/private`
- `storage/app/public`

Create a separate archive before the final release:

```bash
tar -czf "storage/backups/darltcms_files_pre_v1_$(date +%Y%m%d_%H%M%S).tar.gz" \
  storage/app/private \
  storage/app/public
```

Keep the production `.env` secure and outside GitHub. If it is backed up, store it separately in a protected location because it contains secrets.

## 2. Run the final release check

Run:

```bash
php artisan dar:release-check
```

The command is read-only. It does not change records.

For `v1.0.0`, the command must end with:

```text
FINAL RELEASE READY
```

It checks both:

- production security/configuration; and
- stored DAR-LTCMS data integrity.

Warnings such as production mail still using the log driver or a debug-level production log will make the final release check fail until corrected.

For machine-readable output:

```bash
php artisan dar:release-check --json
```

## 3. Production settings that must be correct

The production `.env` should use the following baseline. Values containing secrets are intentionally not shown here.

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://darltcms.me
LOG_LEVEL=warning
FILESYSTEM_DISK=local
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

For password-recovery email to work for real users, production should use a real mail delivery service rather than `MAIL_MAILER=log`.

Do not create a `public/storage` symlink. Uploaded administrative records are intentionally kept behind authenticated routes.

## 4. Deploy and verify the exact version

Merging to `main` automatically deploys the current version to CloudPanel.

After the deployment completes, the server stores the exact deployed GitHub commit in:

```text
.release-commit
```

Check it with:

```bash
cat /home/darltcms/htdocs/darltcms.me/.release-commit
```

The value should match the intended `main` commit on GitHub.

## 5. Final smoke test after deployment

A smoke test is a short check that the most important parts still open and work after deployment.

### Public/basic

- Open `https://darltcms.me`.
- Open `https://darltcms.me/up` and confirm the health endpoint responds normally.
- Confirm the login page loads without broken styling.

### DAR Staff

- Sign in using an authorized Staff test account.
- Open Dashboard.
- Open Landowners, Parcels, Landholdings, and Applications.
- Open an application review page.
- Confirm supporting documents remain protected.
- Confirm a finalized application is locked against edits/uploads.
- Open a released and a denied LTC Form No. 5.
- Confirm Form No. 5 uses 8.5 x 13 in. layout, correct LTC number, GRANTED/DENIED result, parcel information, signatory, and notarial details.
- Open Monitoring/Reports and confirm filters and print view work.
- Open Audit Logs and confirm important actions remain traceable.

### Landowner

- Confirm the Landowner can only see records/applications tied to their own account.
- Confirm the Landowner cannot create an application.
- Confirm another Landowner's parcel/application/result cannot be opened directly.

### Geodetic Personnel

- Confirm parcel/reference/map information can be viewed as intended.
- Confirm editing/approval actions are not available.

## 6. If something goes wrong after release

Do not immediately restore a database backup for a visual or code-only problem.

### Code/UI problem only

The safest first action is to revert the problem commit or pull request in GitHub and let the normal `main` deployment publish the previous code again.

The production deployment does not delete private uploads or the database.

### Data/database problem

1. Stop new changes while investigating:

```bash
php artisan down
```

2. Create another backup of the current state before restoring anything.
3. Identify the correct pre-release `.dump` file.
4. Restore only after confirming that restoration is necessary. Database restoration is destructive and should not be used just to fix a frontend/code issue.
5. Bring the system back online after verification:

```bash
php artisan up
```

A PostgreSQL restore should be performed by an authorized administrator/developer who has confirmed the target database and backup file. Do not run a destructive restore command from copied instructions without checking both first.

## 7. When to create `v1.0.0`

Create the final version tag/release only when all of these are true:

- the final automatic test run is green;
- `php artisan dar:release-check` is clean on production;
- a fresh database backup exists;
- a fresh private-file backup exists;
- the deployed `.release-commit` matches the intended `main` commit;
- the post-deployment smoke test passes;
- final thesis/user documentation matches the actual system behavior.

Only then should the project baseline be tagged as `v1.0.0`.
