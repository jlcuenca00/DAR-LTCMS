# Barebones Tester Reset Checklist (Testing Only)

This checklist is for preparing a **local or staging tester database**. It is not the DAR-LTCMS production `v1.0.0` release procedure.

> **Never run `migrate:fresh` on production.** It deletes the target database contents.

For the actual production release process, use `docs/RELEASE_PREPARATION.md`.

## 1. Confirm the test code baseline

```bash
git status
git log --oneline -5
```

Expected: the working tree is clean and the intended branch/commit is checked out.

## 2. Reset the test database

```bash
php artisan migrate:fresh --seeder=BarebonesTesterSeeder
```

Expected test state:

```text
5 Staff tester accounts
required document reference list
0 demo Landowners
0 demo Geodetic accounts
0 demo Parcels
0 demo Landholdings
0 demo Applications
0 demo Source Records
0 demo Notifications
0 demo Audit Logs
```

The primary starting account is:

```text
Email: staff.tester@dar-ltcms.local
Password: password
```

All seeded credentials are testing-only and must not be used as production accounts.

## 3. Build and test

```bash
composer install
npm ci
npm run build
php artisan test
```

Expected: dependencies install, frontend build completes, and tests pass.

## 4. Manual test login

Confirm:

```text
[ ] Staff login works
[ ] Dashboard loads
[ ] No demo applications are shown
[ ] No demo Landowners are shown
[ ] No demo Parcels are shown
[ ] No demo Notifications are shown
[ ] Required-document configuration remains available
```

## 5. Optional tester database export

If a portable **testing-only** database export is needed:

```bash
mkdir -p final_exports
pg_dump -U postgres -h 127.0.0.1 -p 5432 -d dar_iland \
  -f final_exports/dar_iland_barebones_tester_database.sql
```

Do not confuse this tester export with a production backup. Production release backups use the procedure in `docs/RELEASE_PREPARATION.md`.

## 6. Tester handoff files

Useful files include:

```text
docs/barebones-tester-handoff.md
docs/tester-data-entry-guide.md
docs/final-manual-testing-checklist.md
```

Do not distribute or commit:

```text
.env
production database credentials
production backups
storage/app/private contents
storage/app/public administrative records
```

## 7. Current workflow expectations

New applications use the DAR office workflow ending in either:

- **Released**; or
- **Denied**.

Both are final states. After either final state, editing/uploads are locked and the record remains available for authorized viewing, monitoring, reporting, audit, and clearance output purposes.

A Released clearance does not automatically transfer land ownership or alter registry ownership records.

## 8. Production release reminder

Do **not** create the final `v1.0.0` tag from this barebones tester checklist. The production tag is created only after the production release check, backups, exact deployment verification, and smoke test in `docs/RELEASE_PREPARATION.md` have all passed.
