# DAR-LTCMS Backup and Recovery Procedure

## Create a backup

From the project root in Git Bash:

```bash
bash scripts/backup_dar_ltcms.sh
```

The script creates a timestamped folder under `storage/backups/` containing a PostgreSQL custom-format dump, private uploaded files, a manifest, and checksums.

## Restore into a test database first

```bash
createdb -U postgres dar_iland_restore_test
pg_restore -U postgres -d dar_iland_restore_test --clean --if-exists storage/backups/<backup-folder>/database.dump
```

Extract private files into a separate test project copy:

```bash
tar -xzf storage/backups/<backup-folder>/private-files.tar.gz -C storage/app
```

Run the application against the restored test database and confirm users, applications, documents, final decisions, clearances, and audit logs before restoring production.

## Production rule

Never overwrite the active database without a verified backup, authorized approval, and a tested rollback plan. Database and file backups must be stored outside the application server.
