# DAR-LTCMS Final Release Preparation

This checklist is for the DAR Negros Oriental Provincial Office deployment of DAR-LTCMS.

DAR-LTCMS is a clearance generation, processing, records-management, and monitoring system. Releasing or approving a clearance in this system does **not** automatically transfer land ownership and does **not** alter official registry ownership records.

## 1. Before the final release

Do these checks on the production server before creating the `v1.0.0` release.

### A. Back up the actual production connection

From the production project folder:

```bash
cd /home/darltcms/htdocs/darltcms.me
bash scripts/run_production_backup_with_alert.sh
```

The wrapper runs the encrypted off-site backup and records its output under `darltcms-backup`. A backup failure attempts an email to the privately configured operator; successful backups do not send mail.

Use the server's protected `~/.config/dar-ltcms/backup.env` configuration and `~/.config/dar-ltcms/backup-alert-email` recipient file. Do not overwrite an existing configuration with the example. See [alert setup](BACKUP_ALERT_SETUP.md) and [recovery procedure](RECOVERY_PROCEDURE.md).

The backup includes the database, production `.env`, private uploads in `storage/app/private` and preserved legacy uploads in `storage/app/public`. It resolves the connection actually used by Laravel rather than assuming `127.0.0.1`, a particular database user, or the legacy `dar_iland` database name.

### B. Confirm recoverability

Check that a fresh snapshot exists and that the scheduled job completed successfully. Restore a selected backup into a private, fresh directory and a separate test database. Verify both the database and uploaded files. Never use the live database or live upload directories as test restore targets.

A local dump or archive can be additional protection, but a copy kept only on the production server does not replace the encrypted off-site backup.

### C. Backup behavior and limits

Use `bash scripts/backup_dar_ltcms_production.sh` with the protected configuration described in `scripts/backup.env.example`. Optional retention, staging, snapshot-label, and sampling settings are loaded from that file before defaults are applied. The script backs up PostgreSQL, private/legacy uploads, and the production .env into the encrypted restic repository and removes its temporary dump on exit.

The production backup resolves Laravel's effective default PostgreSQL connection, including `DB_URL`, write-connection settings, and TLS mode/certificate paths. Do not use separate database-target overrides in `backup.env`; configure the application connection itself. The resolver writes null-delimited values only into a private temporary file, removes that file before dumping, and stops before uploading when resolution fails. Database credentials are never printed.

CI verifies full rollback/remigration and a PostgreSQL custom-dump restore in separately named disposable service-container databases. It compares all table records, constraints, triggers, sequences, and application trigger functions and checks restored audit append-only behavior. This checks application recovery mechanics using synthetic records; it does not verify the live off-site snapshot or restore uploaded files.

A successful `pg_restore --list` checks the dump catalog; it does not prove a full restore. Before final turnover, verify the actual server schedule, failure monitoring, latest off-site snapshot, and an isolated database/file restore. These operational checks are not established by repository configuration or CI. Never restore into the live database as a test.

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
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DAILY_DAYS=14
FILESYSTEM_DISK=local
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

For password-recovery email to work for real users, production must use a real delivery transport. `log` and `array` are rejected for production notifications, including when nested inside failover/roundrobin mailers. Delivery failures must reach the caller instead of writing recovery codes or temporary credentials into logs and claiming delivery succeeded.

SMTP uses `MAIL_TIMEOUT=15` by default (valid production range: 1–30 seconds). Use `MAIL_SCHEME=smtp` on port 587 with automatic STARTTLS, or `MAIL_SCHEME=smtps` on port 465 for implicit TLS; `tls` is not a supported scheme. A blank scheme enables Laravel's port-based selection. Production readiness validates the effective active mailer graph and SMTP settings, including `MAIL_URL` overrides, without sending email or exposing credentials. A clean configuration check does not prove inbox delivery.

Current mail notifications are synchronous and no application jobs or scheduled tasks require a worker. Profile email verification is sent after the profile transaction commits; failed sending preserves the saved profile and unverified address and reports the existing warning. If queued delivery is introduced later, configure a supervised worker and deployment restart before enabling it.

Use daily application logs with 1–365 days of retention (14 by default). Readiness checks traverse nested stacks and warn about discarded logs, missing/cyclic/empty channels, debug levels, and invalid daily retention. If single-file logging is intentional, verify server-managed rotation and retention before setting `LOG_EXTERNAL_ROTATION=true`; this flag records an administrator's acknowledgment and does not inspect the server's rotation service. Existing single logs are preserved and need separate archival/rotation.

Deployment clears compiled configuration, routes, and views but preserves runtime cache so active login and other authentication throttles survive releases. Do not use `cache:clear` or `optimize:clear` as routine deployment steps; if application data later needs invalidation, target its keys explicitly.

Do not create a `public/storage` symlink. Uploaded administrative records are intentionally kept behind authenticated routes.

## 4. GitHub production protection

Keep production SSH credentials in the GitHub `production` Environment, keep the `Protect main` ruleset active, and require the exact GitHub Actions check `responsive-browser-tests` before merging to `main`. Branches must be up to date; third-party GitHub Actions must be pinned to immutable commit SHAs.

Production SSH host verification should use a preconfigured `known_hosts` file and `StrictHostKeyChecking=yes`. Obtain the trusted host public key from the server console or another independently authenticated administrative channel. A connection obtained through first-use acceptance or `ssh-keyscan` alone does not establish independent host trust.

Keep the secret-free verification job before production deployment. A failed verification must prevent production synchronization.

When intentionally upgrading a third-party GitHub Action, review the new upstream release/tag first, then update the pinned SHA in a pull request.

## 5. Deploy and verify the exact version

Merging to `main` automatically deploys the current version to CloudPanel. Production runs are serialized without canceling an active deployment.

The workflow enters a pre-rendered maintenance window before copying application files. Users receive a temporary 503 response while dependencies, migrations, private-storage checks, and application caches are updated. A failed copy or update retains maintenance; an already-maintained application is not automatically reopened by a new deployment.

After the update succeeds, the workflow reopens the application and runs `php artisan dar:check-deployment-http`. This checks the configured HTTPS `/up` endpoint, the login form, and the login page's built CSS and JavaScript. Missing assets, redirects, unexpected asset content types, or unhealthy responses fail deployment and restore maintenance. The health endpoint verifies application boot; this smoke test does not replace authenticated role testing or database-integrity checks.

If a deployment fails, inspect its logs and current server state before recovery. Do not run `php artisan up` merely to clear the error: finish or revert the interrupted code update, verify dependencies/migrations and production readiness, then rerun finalization with the intended commit. A new deployment refuses an existing maintenance state so it cannot silently reopen a failed or manually paused deployment.

Only after the live HTTP checks pass, the server atomically records the verified deployed GitHub commit in:

```text
.release-commit
```

Check it with:

```bash
cat /home/darltcms/htdocs/darltcms.me/.release-commit
```

The value should match the intended `main` commit on GitHub.

## 6. Final smoke test after deployment

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
- Open a released Approved LTC Form No. 5 and, if available, a preserved historical Not Approved / Denied output. Historical negative records remain read-only; current workflow creates only Approved decisions.
- Confirm Form No. 5 uses 8.5 x 13 in. layout, correct LTC number, GRANTED/DENIED result, parcel information, signatory, and notarial details.
- Open Monitoring/Reports and confirm filters and print view work.
- Open Audit Logs on Activity and confirm important actions remain traceable without login/logout events.
- Switch to Login History and confirm authentication events and its filtered print report remain available.

### Landowner

- Confirm the Landowner can only see records/applications tied to their own account.
- Confirm the Landowner cannot create an application.
- Confirm another Landowner's parcel/application/result cannot be opened directly.

### Geodetic Personnel

- Confirm parcel/reference/map information can be viewed as intended.
- Confirm approval, application processing, and ownership editing are unavailable.
- Confirm the authorized parcel-geometry editor can save a versioned revision; invalid geometry and stale saves are rejected without overwriting the current version.

## 7. If something goes wrong after release

Do not immediately restore a database backup for a visual or code-only problem.

### Code/UI problem only

Schema changes require separate review: reverting application code does not undo migrations, and historical data-cleanup migrations are intentionally one-way. The full rollback check is for disposable CI databases, not a production recovery procedure.

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

## 8. When to create `v1.0.0`

Create the final version tag/release only when all of these are true:

- strict verified SSH host checking has been restored for production workflows;
- the final automatic test run is green;
- `php artisan dar:release-check` is clean on production;
- a fresh database backup exists;
- a fresh private-file backup exists;
- the deployed `.release-commit` matches the intended `main` commit;
- the post-deployment smoke test passes;
- final thesis/user documentation matches the actual system behavior.

Only then should the project baseline be tagged as `v1.0.0`.
