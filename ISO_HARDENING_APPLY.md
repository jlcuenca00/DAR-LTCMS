# DAR-LTCMS ISO Hardening Patch - Apply Steps

1. Back up the current database and private uploaded files.

```bash
bash scripts/backup_dar_ltcms.sh
```

2. Copy the patch files into the project root, preserving folders.

3. Apply the database changes.

```bash
php artisan migrate
php artisan optimize:clear
npm run build
```

4. Run verification.

```bash
php artisan test
```

5. Confirm these behaviors manually:

- `/register` is unavailable.
- Login and password confirmation use username-based accounts.
- Authenticated pages cannot be restored from browser cache after logout.
- Staff password reset still forces a password change.
- Executable supporting-document uploads are rejected.
- Requirement details can be saved without attaching a file.
- Released and denied applications remain locked.
- Final decision confirmation is required.
- Landowner and geodetic access remains limited to authorized records.
- Map pages show a useful fallback when map resources cannot load.

## Migration note

The hardening migration removes the obsolete `landholding_mutations` table and the `registry_mutated_at` / `registry_mutated_by` columns because automatic ownership transfer and registry alteration are outside DAR-LTCMS scope.
