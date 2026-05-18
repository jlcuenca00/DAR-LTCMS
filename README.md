# Department of Agrarian Reform Land Transfer Clearance and Monitoring System

A web-based clearance generation, application processing, monitoring, parcel/reference review, and records-management system for the **Department of Agrarian Reform Negros Oriental Provincial Office**.

## System scope

This system supports administrative processing and monitoring of land transfer clearance applications. It is designed for authorized DAR Negros Oriental users and linked stakeholders only.

The system covers:

- landowner record management
- parcel record management
- landholding record management
- land transfer clearance application processing
- supporting document upload, metadata indexing, and review
- application status monitoring
- role-based access control
- audit logging
- parcel/reference map review
- monitoring and report generation
- clearance decision output generation

## Critical limitation

This system does **not** automatically transfer land ownership.

This system does **not** automatically mutate Registry of Deeds records.

Approval of a clearance application only means the system may record the final decision, generate the clearance result, lock/finalize the application record, support monitoring/reporting, and audit log the action. Any actual transfer of ownership, registry alteration, title mutation, or legal land transfer remains outside the automatic operational scope of this system and is subject to separate legal and administrative procedures.

## Technology stack

- Laravel 12
- PostgreSQL
- Laravel Breeze authentication
- Blade templates
- Tailwind CSS / Vite
- Leaflet parcel map viewer
- DomPDF for printable/PDF decision outputs
- Google Sans UI font styling

## User roles

### DAR Staff

DAR Staff users are the main system operators. They may:

- manually encode clearance applications
- manage landowner, parcel, landholding, source record, document, and application records
- upload and review supporting documents
- encode selected document metadata/indexing fields
- process clearance applications
- generate clearance decision outputs
- generate monitoring reports
- view audit logs
- manage user accounts and roles

### Landowner

Landowner users are restricted stakeholders. They may:

- view only their own parcel records
- view only their own clearance application status
- view finalized decision output/PDF only for their own linked applications

Landowners do **not** create applications themselves and must never access records belonging to other landowners.

### Geodetic Personnel

Geodetic users have limited, read-only review access. They may:

- review parcel records
- review parcel/reference map information
- open parcel detail views for review

Geodetic users cannot approve applications, cannot view clearance applications, cannot upload documents, cannot generate clearance decisions, and cannot broadly edit ownership/application records.

## Final decision lock rule

When a clearance application reaches either of the following final statuses:

- `approved`
- `not_approved`

The record is treated as final. After final decision:

- editing is locked
- uploads are locked
- backend update attempts are rejected
- UI reflects the locked state
- only authorized viewing, printing, archival, and monitoring actions remain allowed
- the final decision remains traceable through audit logs

## Agricultural classification note

The system includes an internal agricultural classification/status field for parcel and source-record workflows.

Allowed values:

- `private_agricultural`
- `awarded_cloa`
- `emancipation_patent`
- `carp_covered`
- `not_yet_determined`
- `non_agricultural`

This classification supports record organization, filtering, review, and reporting. It is **not** an automatic approval gate, not a land transfer mechanism, and not a reason by itself to mutate ownership records. The system name and normal UI labels remain centered on **Land Transfer Clearance**, not “Agricultural Land Transfer Clearance.”

## Main modules

### Staff modules

- Staff Dashboard
- Clearance Applications
- Application Review
- Supporting Document Upload and Metadata Indexing
- Clearance Decision Output / PDF
- Landowner Records
- Parcel Records
- Landholding Records
- Source Records / Legacy Records
- Source Record Package Encoding
- Source Record Package Import and Preview
- Parcel Map Viewer
- Monitoring Reports
- Audit Log Viewer
- User / Role Management
- Profile Settings

### Landowner modules

- Landowner Dashboard
- Own Parcel Records
- Own Application Status
- Own Finalized Decision Output / PDF
- Privacy-filtered Parcel Map Viewer
- Profile Settings

### Geodetic modules

- Geodetic Dashboard
- Read-only Parcel Records
- Read-only Parcel Details
- Read-only Parcel Map Viewer
- Profile Settings

## Setup notes

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Create environment file

```bash
copy .env.example .env
php artisan key:generate
```

For Git Bash, use:

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure PostgreSQL

Update `.env` with the local PostgreSQL database settings.

Example:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dar_iland
DB_USERNAME=postgres
DB_PASSWORD=123
```

### 4. Run migrations and base seeders

```bash
php artisan migrate:fresh --seed
```

The default `DatabaseSeeder` seeds the required clearance document list.

### 5. Optional demo seeders

Use these only for demo/manual testing data.

```bash
php artisan db:seed --class=LandownerPrivacyDemoSeeder
php artisan db:seed --class=ParcelMapDemoSeeder
```

### 6. Build frontend assets

For development:

```bash
npm run dev
```

For final build:

```bash
npm run build
```

### 7. Run the application

```bash
php artisan serve
```

Open the local Laravel URL shown in the terminal.

## Demo accounts

The `LandownerPrivacyDemoSeeder` creates these manual testing accounts:

| Role | Email | Password | Purpose |
| --- | --- | --- | --- |
| Staff | `staff.demo@test.com` | `password` | Staff encoder account for privacy/demo records |
| Landowner | `landowner.a@test.com` | `password` | Alpha demo landowner |
| Landowner | `landowner.b@test.com` | `password` | Bravo demo landowner |

Privacy test expectation:

- Landowner A must only see Alpha-linked parcels/applications.
- Landowner B must only see Bravo-linked parcels/applications.
- Neither landowner must access the other landowner’s parcel, application, map, or decision output records.

The seeder is for manual testing only. It does not transfer land ownership and does not mutate registry records.

## Useful commands

### Run all tests

```bash
php artisan test
```

### Run targeted tests

```bash
php artisan test --filter=LandownerPrivacyTest
php artisan test --filter=GeodeticReadOnlyTest
php artisan test --filter=FinalDecisionLockTest
php artisan test --filter=AuditLoggingTest
php artisan test --filter=MonitoringReportTest
php artisan test --filter=ParcelAgriculturalStatusTest
php artisan test --filter=ParcelAgriculturalStatusRoleVisibilityTest
```

### Search files without grep

PowerShell example:

```powershell
Select-String -Path resources/views/**/*.blade.php -Pattern "Agricultural Classification" -Context 3,3
```

## Database export commands

### Full PostgreSQL export

PowerShell:

```powershell
$env:PGPASSWORD="123"
pg_dump -h 127.0.0.1 -p 5432 -U postgres -d dar_iland --clean --if-exists --no-owner --no-privileges -F p -f "dar_iland_v0_25_ui_polish_finalization_full_export.sql"
Remove-Item Env:\PGPASSWORD
```

Git Bash:

```bash
PGPASSWORD="123" pg_dump -h 127.0.0.1 -p 5432 -U postgres -d dar_iland --clean --if-exists --no-owner --no-privileges -F p -f "dar_iland_v0_25_ui_polish_finalization_full_export.sql"
```

### Schema-only export, optional

PowerShell:

```powershell
$env:PGPASSWORD="123"
pg_dump -h 127.0.0.1 -p 5432 -U postgres -d dar_iland --schema-only --clean --if-exists --no-owner --no-privileges -F p -f "dar_iland_v0_25_schema_only.sql"
Remove-Item Env:\PGPASSWORD
```

Schema-only export is optional because the Laravel migrations already document the schema structure. Keep it only if your adviser/panel requires a separate database schema file.

### Restore full export

PowerShell:

```powershell
$env:PGPASSWORD="123"
psql -h 127.0.0.1 -p 5432 -U postgres -d dar_iland -f "dar_iland_v0_25_ui_polish_finalization_full_export.sql"
Remove-Item Env:\PGPASSWORD
```

Git Bash:

```bash
PGPASSWORD="123" psql -h 127.0.0.1 -p 5432 -U postgres -d dar_iland -f "dar_iland_v0_25_ui_polish_finalization_full_export.sql"
```

## Final defense checklist

See:

- `docs/final-manual-testing-checklist.md`
- `docs/final-defense-screenshot-checklist.md`
- `docs/thesis-documentation-alignment.md`

## Thesis wording reminder

Always describe this system as:

- an administrative processing and monitoring system
- a decision-support and records-management platform
- a clearance generation system

Do not describe it as:

- an automatic ownership transfer system
- a registry mutation engine
- a replacement for official DAR legal/administrative decision-making
