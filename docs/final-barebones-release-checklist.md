# Final Barebones Release Checklist

Use this checklist before giving the system to a tester.

## 1. Confirm latest code is pushed

```bash
git status
git log --oneline -5
```

Expected:

```text
nothing to commit, working tree clean
```

## 2. Reset database to barebones tester state

```bash
php artisan migrate:fresh --seeder=BarebonesTesterSeeder
```

Expected database state:

```text
1 staff user
required document reference list
0 demo landowners
0 demo parcels
0 demo landholdings
0 demo applications
0 demo source records
0 demo notifications
0 demo audit logs
```

## 3. Build and test

```bash
php artisan test
npm run build
```

Expected:

```text
all tests passed
build successful
```

## 4. Manual login check

Use:

```text
Email: staff.tester@dar-ltcms.local
Password: password
```

Confirm:

```text
[ ] staff login works
[ ] dashboard loads
[ ] no demo applications are shown
[ ] no demo landowners are shown
[ ] no demo parcels are shown
[ ] no demo notifications are shown
[ ] required document checklist still appears when application workflow needs it
```

## 5. Export barebones database

```bash
mkdir final_exports
pg_dump -U postgres -h 127.0.0.1 -p 5432 -d dar_iland -f final_exports/dar_iland_barebones_tester_database.sql
```

## 6. Package files for tester handoff

Include:

```text
app/
bootstrap/
config/
database/
docs/
public/
resources/
routes/
tests/
README.md
artisan
composer.json
composer.lock
package.json
package-lock.json
vite.config.js
tailwind.config.js
postcss.config.js
final_exports/dar_iland_barebones_tester_database.sql
```

Do not include:

```text
vendor/
node_modules/
.env
storage/logs/
```

## 7. Tester instructions to include

Give the tester these docs:

```text
docs/barebones-tester-handoff.md
docs/tester-data-entry-guide.md
docs/final-barebones-release-checklist.md
```

## 8. Final Git tag suggestion

After the barebones state is verified:

```bash
git tag -a v0.27-barebones-tester-handoff -m "v0.27 barebones tester handoff"
git push origin v0.27-barebones-tester-handoff
```
