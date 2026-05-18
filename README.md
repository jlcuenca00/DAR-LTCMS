# Department of Agrarian Reform Land Transfer Clearance and Monitoring System

A web-based clearance generation, application processing, monitoring, parcel/reference review, notification, and records-management system for the **Department of Agrarian Reform Negros Oriental Provincial Office**.

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
- in-app notifications
- audit logging
- parcel/reference map review
- monitoring and report generation
- clearance decision output generation

## Critical limitation

This system does **not** automatically transfer land ownership.

This system does **not** automatically mutate Registry of Deeds records.

Approval of a clearance application only means the system may record the final decision, generate the clearance result, lock/finalize the application record, support monitoring/reporting, notify authorized users, and audit log the action. Any actual transfer of ownership, registry alteration, title mutation, or legal land transfer remains outside the automatic operational scope of this system and is subject to separate legal and administrative procedures.

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
- receive role-based in-app notifications

### Landowner

Landowner users are restricted stakeholders. They may:

- view only their own parcel records
- view only their own clearance application status
- view finalized decision output/PDF only for their own linked applications
- receive notifications related only to their own application/status/final decision records

Landowners do **not** create applications themselves and must never access records belonging to other landowners.

### Geodetic Personnel

Geodetic users have limited, read-only review access. They may:

- review parcel records
- review parcel/reference map information
- open parcel detail views for review
- receive limited parcel/source/reference review notifications

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

## In-app notifications

The system includes role-based in-app notifications with a topbar notification bell and recent notification dropdown.

Staff notification triggers:

- clearance application created/encoded
- application submitted for review
- application approved
- application marked not approved

Excluded staff notification triggers:

- supporting document upload
- document metadata/indexing update
- clearance output generated

Clearance output generation is treated as part of the final decision process and does not need a separate staff notification.

Landowner notifications are limited to the landowner's own application status/final decision/output availability. Geodetic notifications are limited to parcel/source/reference review availability or updates. Notifications are user-specific and do not override role-based access control.

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
- In-App Notifications
- Profile Settings

### Landowner modules

- Landowner Dashboard
- Own Parcel Records
- Own Application Status
- Own Finalized Decision Output / PDF
- Privacy-filtered Parcel Map Viewer
- In-App Notifications
- Profile Settings

### Geodetic modules

- Geodetic Dashboard
- Read-only Parcel Records
- Read-only Parcel Details
- Read-only Parcel Map Viewer
- In-App Notifications
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

### 4. Run normal base setup

```bash
php artisan migrate:fresh --seed
```

The default `DatabaseSeeder` seeds the required clearance document list.

### 5. Run barebones tester setup

Use this when preparing a clean system for testers who will encode their own data through the UI.

```bash
php artisan migrate:fresh --seeder=BarebonesTesterSeeder
```

This produces a clean tester-ready state:

- one active staff account
- required document reference records
- no demo landowners
- no demo geodetic users
- no demo parcels
- no demo landholdings
- no demo source records
- no demo clearance applications
- no demo uploaded documents
- no demo notifications
- no demo audit logs

Starting account:

```text
Email: staff.tester@dar-ltcms.local
Password: password
Role: Staff
```

The tester should then create records through the system interface.

### 6. Optional demo seeders

Use these only for demo/manual privacy or map testing. Do not run them for barebones tester handoff.

```bash
php artisan db:seed --class=LandownerPrivacyDemoSeeder
php artisan db:seed --class=ParcelMapDemoSeeder
```

### 7. Build frontend assets

For development:

```bash
npm run dev
```

For final build:

```bash
npm run build
```

### 8. Run the application

```bash
php artisan serve
```

## Barebones tester workflow

After logging in as the starting staff account, the tester should fill the system through the UI:

1. Create needed users through User / Role Management.
2. Create landowner records.
3. Link landowner user accounts to landowner records when landowner portal testing is needed.
4. Create parcel records.
5. Create/link landholding records.
6. Optionally encode source record packages/reference records.
7. Encode clearance applications manually as staff.
8. Upload supporting documents and encode document metadata/indexing fields.
9. Submit applications for review.
10. Test approved and not-approved decision behavior.
11. Confirm final decision locking.
12. Confirm audit log entries.
13. Confirm notification dropdown behavior.
14. Confirm landowner privacy restrictions.
15. Confirm geodetic read-only access.
16. Confirm monitoring reports and parcel map views.

See:

- `docs/barebones-tester-handoff.md`
- `docs/tester-data-entry-guide.md`
- `docs/final-barebones-release-checklist.md`

## Final database export

For barebones tester handoff:

```bash
mkdir final_exports
pg_dump -U postgres -h 127.0.0.1 -p 5432 -d dar_iland -f final_exports/dar_iland_barebones_tester_database.sql
```

For a populated demo export, use a different file name to avoid confusing demo data with the barebones tester database.
