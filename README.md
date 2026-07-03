# DAR-LTCMS

**Department of Agrarian Reform – Land Transfer Clearance and Monitoring System**

DAR-LTCMS is a web-based administrative platform for the **Department of Agrarian Reform Negros Oriental Provincial Office**. It supports land transfer clearance processing, clearance form generation, application monitoring, role-based access, document review, reporting, and audit tracking.

---

## Table of Contents

- [Project Overview](#project-overview)
- [Core System Purpose](#core-system-purpose)
- [Main Features](#main-features)
- [User Roles](#user-roles)
- [Application Workflow](#application-workflow)
- [Land Record Flow](#land-record-flow)
- [LTC Forms and Outputs](#ltc-forms-and-outputs)
- [Security and Integrity Rules](#security-and-integrity-rules)
- [Tech Stack](#tech-stack)
- [Local Setup](#local-setup)
- [Database Setup](#database-setup)
- [Common Development Commands](#common-development-commands)
- [Testing Checklist](#testing-checklist)
- [Beta Deployment Notes](#beta-deployment-notes)
- [Repository Notes](#repository-notes)

---

## Project Overview

DAR-LTCMS was developed as a thesis/capstone system for managing land transfer clearance-related records and workflows within the DAR Negros Oriental Provincial Office.

The system centralizes the following office records and activities:

- Landowner records
- Parcel records
- Landholding records
- Source package/reference records
- Land transfer clearance applications
- Supporting document uploads and review
- Clearance form generation
- Application status monitoring
- Audit logs and activity tracking
- Monitoring reports

---

## Core System Purpose

DAR-LTCMS provides a structured platform for staff-assisted clearance processing. Approval or release of an application records the final clearance decision, generates the appropriate output, preserves the decision trail, and supports office monitoring and reporting.

Actual legal and administrative procedures remain governed by the authorized DAR process and applicable documentary requirements.

---

## Main Features

| Module | Description |
|---|---|
| Dashboard | Role-based overview of records, applications, and monitoring data |
| Landowner Management | Create and maintain landowner profiles and linked user accounts |
| Parcel Records | Store parcel details, title/tax declaration references, classification, area, and map geometry |
| Landholding Records | Connect landowners to parcel-based landholding records |
| Source Packages | Store documentary/reference source records for review and traceability |
| Clearance Applications | Encode, review, endorse, approve/release, deny, and monitor applications |
| Supporting Documents | Upload, view, and review application-related files |
| LTC Form Outputs | Generate printable/PDF outputs for required LTC forms |
| Map Viewer | Review parcel/map-based information using Leaflet |
| Notifications | Notify authorized users about important application events |
| Monitoring Reports | View office-level reports and clearance processing summaries |
| Audit Logs | Track important actions with actor, timestamp, and record context |

---

## User Roles

### DAR Staff

DAR Staff are the primary system operators. They can:

- Manage landowner, parcel, landholding, source package, and application records
- Manually encode clearance applications
- Upload and review supporting documents
- Process workflow actions through the application review page
- Generate LTC forms and monitoring reports
- View audit trails and application history

### Landowner

Landowner users have controlled self-service access. They can:

- View their own linked parcel and landholding records
- View their own clearance application status
- View released/available clearance-related outputs connected to their records

### Geodetic Personnel

Geodetic users have limited review access. They can:

- Review parcel, reference, and map-based information
- View relevant parcel geometry and location data
- Add or view verification-related notes when enabled by the workflow

---

## Application Workflow

The current clearance workflow follows office-style processing stages:

```text
Pending Review by Legal Officer
        ↓
Endorsed to LTI Division
        ↓
Endorsed to Chief Legal
        ↓
Endorsed to PARPO II
        ↓
For Releasing
        ↓
Released
```

Applications may also reach a final denied outcome when the review decision requires it.

Final decision states lock the record for preservation. Viewing, reporting, printing, and archival actions remain available to authorized users.

---

## Land Record Flow

Recommended record creation order:

```text
Landowner Record
        ↓
Parcel Record
        ↓
Landholding Record
        ↓
Land Transfer Clearance Application
```

A landholding is based on parcel information, so parcel records should be encoded before creating landholding records.

---

## LTC Forms and Outputs

DAR-LTCMS currently supports application-related LTC outputs such as:

- **LTC Form No. 1** – Application/data form
- **LTC Form No. 3** – Acknowledgment receipt / printable application output
- **LTC Form No. 4** – Review checklist
- **LTC Form No. 5** – Endorsement-related output

PDF and print views use a formal office style suitable for review, filing, and release.

---

## Security and Integrity Rules

The system follows these integrity principles:

- Strict role-based access control
- Landowner access limited to their own linked records
- Geodetic access focused on parcel/reference/map review
- Staff-controlled application encoding and processing
- Audit logging for significant actions
- Final decision locking for approved/released and denied records
- Protected supporting documents and clearance outputs
- Traceable record history through timestamps and actor-based logs

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 |
| Language | PHP 8.4 |
| Database | PostgreSQL |
| Authentication | Laravel Breeze |
| Frontend | Blade, Vite, Tailwind CSS |
| Mapping | Leaflet |
| Local PHP Runtime | Laravel Herd / PHP local environment |
| Package Managers | Composer, npm |

---

## Local Setup

### 1. Clone the repository

```bash
git clone <repository-url>
cd <project-folder>
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install frontend dependencies

```bash
npm install
```

### 4. Create environment file

```bash
cp .env.example .env
```

### 5. Generate application key

```bash
php artisan key:generate
```

---

## Database Setup

The current local PostgreSQL database name is:

```env
DB_DATABASE=dar_iland
```

Recommended `.env` database settings:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dar_iland
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

Run migrations:

```bash
php artisan migrate
```

Run seeders when using prepared development data:

```bash
php artisan db:seed
```

For a fresh reset with seed data:

```bash
php artisan migrate:fresh --seed
```

---

## Common Development Commands

Start the Laravel development server:

```bash
php artisan serve
```

Start the Vite development server:

```bash
npm run dev
```

Build frontend assets:

```bash
npm run build
```

Clear Laravel caches:

```bash
php artisan optimize:clear
```

Create the storage link:

```bash
php artisan storage:link
```

Run tests:

```bash
php artisan test
```

---

## Testing Checklist

Before beta deployment, test the following flows:

### DAR Staff

- Create landowner record
- Create parcel record
- Create landholding record
- Encode new clearance application
- Upload supporting documents
- Submit application for review
- Process workflow endorsements
- Release or deny application
- Print/view LTC forms
- Generate monitoring report
- Confirm audit logs are recorded

### Landowner

- Login successfully
- View own records
- View own application status
- Open available clearance-related outputs
- Confirm unrelated records remain inaccessible

### Geodetic Personnel

- Login successfully
- View parcel/reference/map information
- Review map geometry and parcel details
- Confirm application decision controls remain staff-controlled

### Final Decision Lock

After an application reaches a final decision state, confirm that:

- Editing controls are locked
- Upload controls are locked
- Final status remains visible
- Printable outputs remain accessible to authorized users
- Audit history remains available

---

## Beta Deployment Notes

Recommended production-style settings:

```env
APP_NAME="DAR-LTCMS"
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
DB_DATABASE=dar_iland
```

Before deployment/build:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

For local beta testing, `APP_DEBUG=true` may be used to make errors easier to inspect.

---

## Database Export and Restore

Export local database:

```bash
pg_dump -U postgres -h 127.0.0.1 -p 5432 -d dar_iland --no-owner --no-privileges -f dar_iland_full_export.sql
```

Create database before restore:

```bash
createdb -U postgres dar_iland
```

Restore export:

```bash
psql -U postgres -d dar_iland -f dar_iland_full_export.sql
```

---

## Repository Notes

The active system name is **DAR-LTCMS**.

Older repository, folder, branch, or database labels may still appear during transition. Current application-facing text should use **DAR-LTCMS**.

Recommended main branches:

| Branch | Purpose |
|---|---|
| `main` | Stable project state |
| `beta-deployment` | Final beta testing and deployment preparation |
| `dar-flow-revision` | Previous workflow revision work |

---

## Suggested Git Workflow

Check current branch and changes:

```bash
git branch
git status
```

Stage all project changes:

```bash
git add -A
```

Remove local environment file from staging when needed:

```bash
git restore --staged .env
```

Commit changes:

```bash
git commit -m "Update DAR-LTCMS README"
```

Push current branch:

```bash
git push
```

---

## License / Academic Use

This project was created for academic thesis/capstone purposes and is intended for DAR Negros Oriental Provincial Office workflow evaluation, beta testing, and system demonstration.
