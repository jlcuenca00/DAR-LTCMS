# DAR-LTCMS

**Department of Agrarian Reform – Land Transfer Clearance and Monitoring System**

DAR-LTCMS is a web-based administrative processing, records-management, clearance-generation, and monitoring platform developed for the **Department of Agrarian Reform (DAR) Negros Oriental Provincial Office**. It supports land transfer clearance application processing, related land records, supporting-document review, clearance generation, monitoring, reporting, and auditability.

## Live System

**Production site:** [darltcms.me](https://darltcms.me)

The deployed internal workflow is operated by authorized Legal Clearance Staff, with approved Landowner and Geodetic stakeholder access according to role. Access to functions and records is controlled by role and record ownership.

## Project Scope

DAR-LTCMS supports:

- Landowner record management
- Parcel record management
- Landholding record management
- Legal Clearance Staff-encoded Land Transfer Clearance applications
- supporting-document upload/review and requirement-specific data capture
- application workflow/status monitoring
- role-based access control
- notifications
- audit logging
- map-based Parcel review
- monitoring and report generation
- LTC form and clearance output generation

The platform is an administrative processing and decision-support system. **Approved** is the only current final administrative clearance decision recorded by DAR-LTCMS. Issues that prevent approval remain open through **Compliance Required / Request Compliance** and resume at the saved workflow stage after resolution. Older Not Approved / Denied records remain historical and read-only. Release of the signed result is tracked separately. Neither approval nor release automatically transfers land ownership, mutates Registry of Deeds records, or conclusively executes a legal land transfer. Any actual ownership transfer or registry alteration remains subject to separate legal and administrative procedures outside DAR-LTCMS automatic operations.

## User Roles

### Legal Clearance Staff

The internal Staff workspace is operated by the small Legal Division team responsible for clearance entry, processing records, monitoring, and exit/release tracking. The stored role value remains `staff` for compatibility.

Legal Clearance Staff can:

- encode/manage Landowner, Parcel, and Landholding records
- manually encode Clearance Applications
- upload/review supporting documents
- record workflow movements/results received from LTID, Chief Legal, PARPO II, the cashier, and other DAR offices
- record the official PARPO II decision while remaining identified as the system recorder
- generate/view LTC forms and final clearance outputs
- monitor application status/history
- generate reports
- review audit trails
- manage authorized system accounts

LTID, Chief Legal, PARPO II, cashier, and other DAR offices are tracked administrative authorities/stages, not separate DAR-LTCMS login roles.

### Landowner

Landowner accounts are restricted to records tied to the authenticated Landowner. They can:

- view their own linked Parcel/Landholding information
- view their own Clearance Application status
- view their own authorized final output when available

Landowners do **not** create applications and must never access another Landowner's records.

### Geodetic Personnel

Geodetic users have limited technical access. They can review authorized Parcel/reference/map information and, where explicitly enabled, edit only parcel map geometry through versioned/concurrency-protected tools. They do not edit ownership/application decision records and are not primary application decision users.

## Current Application Workflow

1. Legal Completeness Review
2. Payment / Official Receipt Recording
3. With LTID for Verification
4. Returned to Legal Division
5. Legal Evaluation / CSW Preparation
6. With Chief Legal for Review
7. With PARPO II for Decision
8. PARPO II Decision Ready to Record
9. Approved — final decision

After approval, the separate delivery record progresses from signed Form No. 5 / Ready for Release to Released to Client.

**Approved** is the only current final application decision state. Once Approved is recorded, substantive editing and supporting-document changes are locked by the UI, backend, and model-level integrity guard.

**Request Compliance** may be used from any active workflow stage when an issue blocks processing. It keeps the same application open and resumes the saved stage after the compliance notice is resolved.

Client release is tracked separately through the release status. Recording a release never changes the final Approved decision and never transfers ownership or mutates registry records.

Historical database values such as `released`, `not_approved`, `denied`, `pending_review`, and `draft` remain readable only for backward compatibility and cannot be created as current negative decisions.

## Core Modules

| Module | Purpose |
|---|---|
| Dashboard | Role-based overview of applications, records, and monitoring information |
| Landowner Records | Maintain Landowner profiles and linked accounts |
| Parcel Records | Store Parcel details, title/tax declaration references, area, classification, and map geometry |
| Landholding Records | Maintain administrative Landowner–Parcel relationships |
| Source / Reference Records | Preserve supporting reference/provenance information used during review |
| Clearance Applications | Encode, review, endorse, record final decisions, track release, and monitor applications |
| Supporting Documents | Upload, view, and review requirement-specific document information |
| LTC Forms and Outputs | Generate office forms, printable records, and final clearance outputs |
| Parcel Map | Review mapped agricultural Parcel information |
| Notifications | Surface important authorized application events |
| Monitoring and Reports | Produce office-level monitoring summaries and printable reports |
| Audit Logs | Activity shows record/workflow actions; Login History separately shows authentication events |
| Administration | Manage authorized accounts and role assignments |

## LTC Forms and Outputs

DAR-LTCMS handles received requirements and generated outputs differently:

| Form | Current system handling |
|---|---|
| LTC Form No. 1 — Notarized Application | Received supporting requirement; Staff upload and record its details |
| LTC Form No. 2 — MARPO Certification | Received supporting requirement; Staff upload and review its details |
| LTC Form No. 3 — Acknowledgment Receipt | Printable application output with a PDF route |
| LTC Form No. 4 — Attestation and Recommendation | Staff record review findings and generate the PDF output |
| LTC Form No. 5 — Land Transfer Clearance | Final clearance output preserved with the decision |

Forms No. 1 and 2 are not documented as separate generated forms: they are received requirements in the current implementation.

For LTC Form No. 5, the current implementation preserves annual LTC numbering/page references, linked Parcel details, GRANTED output for current Approved decisions, preserved DENIED rendering only for historical negative records, the recorded PARPO II decision officer/signatory from immutable final-decision metadata, notarial details, and 8.5 x 13 inch print/PDF behavior.

Final outputs remain administrative clearance records only and do not automatically alter ownership or registry records.

## Security, Integrity, and Auditability

DAR-LTCMS implements controlled access and traceability through:

- strict role-based access control
- Landowner record isolation
- limited Geodetic access
- Legal Clearance Staff-controlled application encoding and workflow recording
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

The `.env.example` PostgreSQL database name defaults to `dar_iland`; use a separate database for each local or test environment.

## Local Development Setup

Use a fresh local checkout and a separate empty PostgreSQL database. These instructions are for development, not the production server.

The automated workflows use PHP 8.4, PostgreSQL 18 and Node.js 22. Install Composer and npm, and enable PHP's PostgreSQL driver (`pdo_pgsql`) and the extensions required by `composer.lock`.

1. Clone the repository and enter its folder:

   ```bash
   git clone https://github.com/jlcuenca00/DAR-LTCMS.git
   cd DAR-LTCMS
   composer install
   ```

2. Composer normally creates `.env` from `.env.example` on a new checkout. If it does not exist, copy the example with `cp .env.example .env` (PowerShell: `Copy-Item .env.example .env`). Keep an existing `.env` rather than overwriting it.

3. Create a dedicated empty database, for example `dar_ltcms_local`, through pgAdmin or PostgreSQL tools. In `.env`, keep `APP_ENV=local`, set `DB_DATABASE=dar_ltcms_local`, and enter your local database host, port, username and password. The example's legacy default is `dar_iland`; verify the actual target before running migrations or test seeders. Never point this checkout at production.

4. Prepare the application:

   ```bash
   php artisan key:generate
   php artisan migrate
   npm ci
   npm run build
   ```

5. For an empty, disposable tester database, follow the [tester handoff](docs/barebones-tester-handoff.md). Its seeder clears records even when called on its own; never run it against a database you need to keep. The testing-only initial username is `staff.tester` after that setup. Public registration is not a way to create Staff accounts.

6. Start the local website:

   ```bash
   php artisan serve
   ```

   Open [127.0.0.1:8000](http://127.0.0.1:8000). For frontend development, run `npm run dev` in another terminal.

The local example sends mail to application logs, not an inbox. Use a test mail service if you need to check email delivery. Current application notifications are synchronous; a queue worker is not required for the implemented flows.

Create a separate empty `dar_iland_beta_testing` PostgreSQL database for the default `phpunit.xml` configuration before running `php artisan test`. Verify the effective test connection and credentials first: database tests may reset records in that target. Never run them against production.

## Production and Release Operations

The protected `main` branch is the production source baseline. The GitHub Actions check `responsive-browser-tests` must pass and the branch must be up to date before merging. Merging to `main` triggers the CloudPanel deployment workflow.

Production secrets, `.env`, database backups, and private administrative uploads are intentionally excluded from source deployment/commits.

Before `v1.0.0`, the production server must pass:

```bash
php artisan dar:release-check
```

and the database/private-file backup, exact deployment verification, and smoke-test requirements in [release guide](docs/RELEASE_PREPARATION.md).

## Canonical Documentation

Use these files as the current project reference:

- [system baseline](docs/FINAL_SYSTEM_BASELINE.md) – canonical scope, roles, workflow, final states, Form 5, and system boundaries
- [thesis alignment guide](docs/thesis-documentation-alignment.md) – wording/diagram rules for thesis alignment
- [manual testing checklist](docs/final-manual-testing-checklist.md) – final controlled UAT checklist
- [release guide](docs/RELEASE_PREPARATION.md) – production backup, release check, smoke test, and rollback procedure
- [tester handoff](docs/barebones-tester-handoff.md) – local/staging tester reset behavior
- [tester data-entry guide](docs/tester-data-entry-guide.md) – current tester data-entry workflow/fields

## Project Status

**Status:** Release candidate; Stage 1 audit complete as of 7 October 2026. No final `v1.0.0` release is claimed.

Stage 1 closed with a clean live record/configuration check, an actual isolated backup/database/file recovery, confirmed recovery and backup-alert email receipt, and a successful deployment. Jake also confirmed the live application, map and Form No. 5 preview. These are dated checks of the current mock dataset, not formal evaluator results or a guarantee of future operation.

Stage 2 repository documentation has been aligned with this baseline. Stage 3 manuscript alignment is next. Final visual refinement is reserved until after Stage 3.

SSH host-trust hardening remains explicitly deferred until Jake says his defense is finished. It must be completed before the planned `v1.0.0` release. Completing an audit or documentation stage does not authorize that change or the final release.

Follow the [release guide](docs/RELEASE_PREPARATION.md) for fresh checks at the actual release date.

## Academic Context

DAR-LTCMS was developed as a thesis/capstone project and is evaluated as a web-based administrative processing, decision-support, records-management, clearance-generation, and monitoring solution for the DAR Negros Oriental Provincial Office.
