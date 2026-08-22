# DAR-LTCMS

**Department of Agrarian Reform – Land Transfer Clearance and Monitoring System**

DAR-LTCMS is a web-based administrative processing, records-management, clearance-generation, and monitoring platform developed for the **Department of Agrarian Reform (DAR) Negros Oriental Provincial Office**. It supports land transfer clearance application processing, related land records, supporting-document review, clearance generation, monitoring, reporting, and auditability.

## Live System

**Production site:** https://darltcms.me

The deployed system is intended for authorized DAR personnel and approved stakeholders. Access to functions and records is controlled by role and record ownership.

## Project Scope

DAR-LTCMS supports:

- Landowner record management
- Parcel record management
- Landholding record management
- Staff-encoded Land Transfer Clearance applications
- supporting-document upload/review and requirement-specific data capture
- application workflow/status monitoring
- role-based access control
- notifications
- audit logging
- map-based Parcel review
- monitoring and report generation
- LTC form and clearance output generation

The platform is an administrative processing and decision-support system. A **Released** clearance records and generates the administrative clearance result; it does **not** automatically transfer land ownership, mutate Registry of Deeds records, or conclusively execute a legal land transfer. Any actual ownership transfer or registry alteration remains subject to separate legal and administrative procedures outside DAR-LTCMS automatic operations.

## User Roles

### DAR Staff

Staff are the primary operators. They can:

- encode/manage Landowner, Parcel, and Landholding records
- manually encode Clearance Applications
- upload/review supporting documents
- process applications through the authorized DAR office workflow
- generate/view LTC forms and final clearance outputs
- monitor application status/history
- generate reports
- review audit trails
- manage authorized system accounts

### Landowner

Landowner accounts are restricted to records tied to the authenticated Landowner. They can:

- view their own linked Parcel/Landholding information
- view their own Clearance Application status
- view their own authorized final output when available

Landowners do **not** create applications and must never access another Landowner's records.

### Geodetic Personnel

Geodetic users have limited/read-only review access. They can review authorized Parcel/reference/map information but do not broadly edit ownership/application records and are not primary application decision users.

## Current Application Workflow

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

A separate application may end in **Denied** when required by the authorized decision.

**Released** and **Denied** are the current final user-facing states. Once final, editing and supporting-document changes are locked by the UI and backend; authorized viewing, monitoring, reporting, audit, and archival access remain available.

Legacy database values such as `approved` and `not_approved` may still be recognized for historical compatibility, but current screens map them to **Released** and **Denied**.

## Core Modules

| Module | Purpose |
|---|---|
| Dashboard | Role-based overview of applications, records, and monitoring information |
| Landowner Records | Maintain Landowner profiles and linked accounts |
| Parcel Records | Store Parcel details, title/tax declaration references, area, classification, and map geometry |
| Landholding Records | Maintain administrative Landowner–Parcel relationships |
| Source / Reference Records | Preserve supporting reference/provenance information used during review |
| Clearance Applications | Encode, review, endorse, release, deny, and monitor applications |
| Supporting Documents | Upload, view, and review requirement-specific document information |
| LTC Forms and Outputs | Generate office forms, printable records, and final clearance outputs |
| Parcel Map | Review mapped agricultural Parcel information |
| Notifications | Surface important authorized application events |
| Monitoring and Reports | Produce office-level monitoring summaries and printable reports |
| Audit Logs | Record significant actions with actor, timestamp, and record context |
| Administration | Manage authorized accounts and role assignments |

## LTC Forms and Outputs

DAR-LTCMS supports LTC-related forms used in the application workflow, including:

- **LTC Form No. 1** – application/data form
- **LTC Form No. 2** – acknowledgment/certification-related requirement
- **LTC Form No. 3** – acknowledgment receipt / printable application output
- **LTC Form No. 4** – review checklist
- **LTC Form No. 5** – final Land Transfer Clearance certification/output

For LTC Form No. 5, the current implementation preserves annual LTC numbering/page references, linked Parcel details, GRANTED/DENIED output, signatory **ENGR. MANUEL M. GALON, JR., OIC PARPO II**, notarial details, and 8.5 x 13 inch print/PDF behavior.

Final outputs remain administrative clearance records only and do not automatically alter ownership or registry records.

## Security, Integrity, and Auditability

DAR-LTCMS prioritizes government-grade traceability and controlled access through:

- strict role-based access control
- Landowner record isolation
- limited Geodetic access
- Staff-controlled application encoding/processing
- protected supporting documents/source scans
- final-decision locking
- timestamped actor-based audit logs
- preserved application/decision history
- server-side authorization for sensitive actions
- controlled account administration
- production security/configuration readiness checks
- production dependency security audits

## Technology Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 |
| Server Language | PHP 8.4 |
| Database | PostgreSQL 18 |
| Authentication | Laravel Breeze |
| Frontend | Blade, Tailwind CSS, Vite |
| Mapping | Leaflet |
| Package Management | Composer and npm |
| Deployment | Linux server with CloudPanel |

The local/development PostgreSQL database name remains `dar_iland`.

## Production and Release Operations

The protected `main` branch is the production source baseline. Merging to `main` triggers the CloudPanel deployment workflow.

Production secrets, `.env`, database backups, and private administrative uploads are intentionally excluded from source deployment/commits.

Before `v1.0.0`, the production server must pass:

```bash
php artisan dar:release-check
```

and the database/private-file backup, exact deployment verification, and smoke-test requirements in `docs/RELEASE_PREPARATION.md`.

## Canonical Documentation

Use these files as the current project reference:

- `docs/FINAL_SYSTEM_BASELINE.md` – canonical scope, roles, workflow, final states, Form 5, and system boundaries
- `docs/thesis-documentation-alignment.md` – wording/diagram rules for thesis alignment
- `docs/final-manual-testing-checklist.md` – final controlled UAT checklist
- `docs/RELEASE_PREPARATION.md` – production backup, release check, smoke test, and rollback procedure
- `docs/barebones-tester-handoff.md` – local/staging tester reset behavior
- `docs/tester-data-entry-guide.md` – current tester data-entry workflow/fields

## Project Status

**Status:** Release candidate / production validation pending.

The repository baseline has completed responsive hardening, UI/UX refinement, data-integrity hardening, audit/notification/reporting hardening, production/security hardening, performance pass, final automated UAT coverage, LTC Form No. 5 finalization, and release-preparation hardening.

The final `v1.0.0` tag must not be created until the live production `dar:release-check`, backups, exact deployed commit verification, and post-deployment smoke test are actually completed.

## Academic Context

DAR-LTCMS was developed as a thesis/capstone project and is evaluated as a web-based administrative processing, decision-support, records-management, clearance-generation, and monitoring solution for the DAR Negros Oriental Provincial Office.
