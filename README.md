DAR-LTCMS
Department of Agrarian Reform Land Transfer Clearance and Monitoring System
DAR-LTCMS is a web-based administrative processing, clearance generation, records-management, and monitoring system for the Department of Agrarian Reform Negros Oriental Provincial Office. The system supports DAR staff in encoding land transfer clearance applications, managing landowner/parcel/landholding records, reviewing supporting documents, generating LTC-related outputs, tracking application status, and producing monitoring reports.
The project is developed as a thesis/capstone system for a government-office workflow. It is designed around traceability, role-based access, document review, audit logging, and controlled final decision records.
---
Project Context
Item	Details
System name	DAR-LTCMS
Meaning	Land Transfer Clearance and Monitoring System
Office scope	DAR Negros Oriental Provincial Office
Platform	Web-based system
Main users	DAR Staff, Landowner, Geodetic Personnel
Main purpose	Clearance application processing, clearance generation, record keeping, status monitoring, and reporting
Database name	`dar_iland`
Current development branch	`beta-deployment`
Previous major work branch	`dar-flow-revision`
Old repo/folder naming	Some local/repository references may still use DAR-iLAND naming until fully renamed
---
Core System Purpose
DAR-LTCMS helps the DAR Negros Oriental Provincial Office manage the administrative workflow for land transfer clearance processing. The system centralizes records and workflow actions related to:
landowner record management
parcel record management
landholding record management
source package/reference record management
land transfer clearance application encoding
supporting document upload and review
application status tracking
LTC form preview and PDF generation
application decision recording
monitoring and report generation
role-based user access
audit logging
Approval or release of an application records the clearance decision and makes the clearance output available through the system. Actual legal transfer, registry alteration, and ownership mutation are handled through the proper separate legal and administrative procedures.
---
Technology Stack
Layer	Technology
Backend	Laravel 12
Language	PHP 8.2+; local target used in development: PHP 8.4 through Herd Lite
Frontend	Blade, Tailwind CSS, Alpine.js
Build tool	Vite
Database	PostgreSQL
PDF generation	`barryvdh/laravel-dompdf`
Authentication scaffold	Laravel Breeze
Map viewer	Leaflet-based parcel map viewer
Package manager	Composer, npm
---
Main User Roles
DAR Staff
DAR Staff are the primary system operators. Staff users handle encoding, record management, workflow processing, document review, final decision recording, and monitoring outputs.
Staff functions include:
manage user accounts
create and manage landowner records
create and manage parcel records
create and manage landholding records
create and manage source packages/reference records
encode land transfer clearance applications
link transferor and transferee records
upload and review supporting documents
submit applications for review
process application workflow stages
approve/release or deny applications through authorized actions
generate LTC form previews and PDFs
view audit logs
generate monitoring reports
access the staff parcel map viewer
Landowner
Landowner users have a self-service viewing portal for their own records and application status.
Landowner functions include:
view own dashboard
view own application status
view own decision output when available
view own parcel records
view map-based parcel information tied to their records/applications
Geodetic Personnel
Geodetic users have a review-oriented portal focused on parcel and map information.
Geodetic functions include:
view geodetic dashboard
review parcel records
review map-based parcel information
inspect parcel location/reference details
support parcel verification through read-oriented access
---
Recommended Data Flow
The intended encoding flow is:
```text
Landowner Record
      ↓
Parcel Record
      ↓
Landholding Record
      ↓
Land Transfer Clearance Application
      ↓
Document Upload and Review
      ↓
Application Workflow Processing
      ↓
Clearance Decision Recording
      ↓
Clearance Output / Monitoring / Reports
```
A landholding should be based on an existing parcel record because landholding data depends on parcel identity, area, location, classification, title/tax declaration references, and map information.
---
Main Modules
1. Authentication and Role-Based Access
The system uses Laravel Breeze authentication with role-based routing and access controls.
Main route areas:
`/staff/*` for DAR Staff
`/landowner/*` for Landowner users
`/geodetic/*` for Geodetic Personnel
`/notifications` for authenticated notification access
`/profile` for authenticated profile management
2. Staff Dashboard
The staff dashboard gives DAR Staff a central view of operational counts, application activity, record status, and entry points to major modules.
3. Landowner Records
Landowner records store personal and address details used in application processing and parcel/landholding association.
Current landowner record fields:
first name
middle name
last name
suffix
contact number
linked landowner user account / no linked account
address
municipality
barangay
province
4. Parcel Records
Parcel records represent land parcels used for clearance processing and monitoring.
Typical parcel information includes:
parcel reference/code
title number
tax declaration number
area in hectares
land classification/status
province
municipality/city
barangay
map/polygon coordinates
remarks/reference notes
Parcel records support the map viewer and provide the base reference for landholding records.
5. Landholding Records
Landholding records associate a landowner with parcel-based landholding information. They help support aggregate landholding review, monitoring, and application preparation.
Landholding records should be created after the related parcel record is encoded.
6. Source Packages / Reference Records
Source packages are documentary/reference workspaces used to encode, review, and link source information. They help staff organize old records, imported references, parcel references, landowner references, and supporting source files.
Source packages support:
source/reference data entry
source file upload
linking to parcel records
linking or creating landowner records from source data
creating parcel records from source data
source package import workflow
archive workflow using a dedicated modal
Source packages serve as documentary/reference records for administrative review and traceability.
7. Land Transfer Clearance Applications
DAR Staff encode applications manually. Applications include transferor/transferee information, linked landowner records, parcel/application details, supporting documents, review status, workflow stage, and decision output.
The application review page includes:
application summary
transferor and transferee details
linked landowner record controls
document checklist / requirement sidebar
Form No. 3 preview
Form No. 4 review section
workflow timeline
application actions modal
clearance output links when available
8. Supporting Document Review
The system maintains a reference list of required documents based on LTC processing needs. Documents may be mandatory, case-dependent, or reference-only.
Current seeded document types include:
Transferor / Application Intake
Official Receipt (LTC Fee Payment)
Electronic Copy of Title
Recent Tax Declaration (if available)
Deed or Document to be Registered
Death Certificate (if applicable)
Affidavit of Transferor
Municipal Assessor's Certificate of Aggregate Landholding
City Assessor's Certificate of Aggregate Landholding
Provincial Assessor's Certificate of Aggregate Landholding
Transferee
Affidavit of Transferee
Death Certificate (if applicable)
Municipal Assessor's Certificate of Aggregate Landholding
City Assessor's Certificate of Aggregate Landholding
Provincial Assessor's Certificate of Aggregate Landholding
MARPO Certification (LTC Form No. 2)
Document upload/review supports application acceptance and review. Recent Tax Declaration should appear once as `Recent Tax Declaration (if available)`.
9. LTC Forms and Outputs
The system includes LTC-related printable/PDF outputs.
Implemented/important outputs:
LTC Form No. 1 related data intake alignment
LTC Form No. 2 / MARPO certification requirement reference
LTC Form No. 3 acknowledgement receipt preview/PDF
LTC Form No. 4 review checklist preview/PDF
LTC Form No. 5 endorsement-related output/flow integration
clearance decision output and PDF
Print/PDF outputs should use formal black/gray styling. Web previews may use compact cards and interface styling for readability.
10. Application Workflow
Current workflow stages follow the revised DAR office practice flow:
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
Denied / Not Approved is a final decision path.
Final states:
Released / Approved
Denied / Not Approved
When an application reaches a final state, editing and uploads are locked. The record remains available for authorized viewing, output generation, monitoring, reporting, audit review, and archival reference.
11. Notifications
The notification system supports role-relevant updates.
Staff notification triggers are limited to:
application created/encoded
application submitted for review
application approved/released
application marked denied/not approved
Landowner notifications focus on their own application status, final decision, and output availability. Geodetic notifications support parcel/reference review visibility where applicable.
Notification UI behavior:
bell opens a notification panel
click outside closes the panel
unread badge clears when the panel is closed
new items may remain visually highlighted while the panel is open
clicking a notification opens the related page and marks it read
“See all notifications” opens the notifications page
12. Map Viewer
The system uses a Leaflet-based parcel map viewer. Map functionality supports staff, landowner, and geodetic views according to role permissions.
Map-related functions include:
viewing parcel locations
reviewing parcel polygon/reference coordinates
inspecting map-based parcel details
supporting parcel review during clearance processing
13. Monitoring Reports
Monitoring reports help staff review application activity, record status, clearance processing progress, and decision outcomes. Reports support administrative monitoring and thesis evaluation of processing visibility.
14. Audit Logs
Important system actions are recorded with actor-based and timestamped audit trails. Audit logs support accountability, traceability, and review of important events.
Examples of audit-relevant actions:
application creation
submission for review
workflow status changes
approval/release
denial/not approved decision
document actions
important record updates
final decision events
---
Local Development Setup
Requirements
Install the following:
PHP 8.2 or higher
Composer
Node.js and npm
PostgreSQL
Git
The development setup previously used:
Laravel 12
PHP 8.4 through Herd Lite
PostgreSQL with database name `dar_iland`
Composer 2.8.x
Node/Vite
Clone / open project
```bash
git clone <repository-url>
cd <project-folder>
```
The repository/folder may still use the old DAR-iLAND naming while the system name displayed in thesis and UI should be DAR-LTCMS.
Install PHP dependencies
```bash
composer install
```
Install frontend dependencies
```bash
npm install
```
Environment file
Copy the example file:
```bash
cp .env.example .env
```
On Windows PowerShell:
```powershell
copy .env.example .env
```
Recommended local database configuration:
```env
APP_NAME="DAR-LTCMS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dar_iland
DB_USERNAME=postgres
DB_PASSWORD=your_postgres_password
```
Generate the Laravel app key:
```bash
php artisan key:generate
```
Create database
Create a PostgreSQL database named:
```text
dar_iland
```
Example command:
```bash
createdb -U postgres dar_iland
```
Run migrations
```bash
php artisan migrate
```
Seed tester accounts and required documents
```bash
php artisan db:seed
```
Current `DatabaseSeeder` calls `BarebonesTesterSeeder`, which clears transactional/demo data, seeds required documents, and creates staff tester accounts.
Default seeded staff users:
Name	Email	Password
DAR Staff Tester	`staff.tester@dar-ltcms.local`	`password`
Jay	`jay.staff@dar-ltcms.local`	`password`
Miles	`miles.staff@dar-ltcms.local`	`password`
Vea	`vea.staff@dar-ltcms.local`	`password`
Lloyd	`lloyd.staff@dar-ltcms.local`	`password`
Landowner and geodetic accounts may be created through staff user management during testing.
Link storage
```bash
php artisan storage:link
```
Build assets
For development with live Vite server:
```bash
npm run dev
```
For built frontend assets:
```bash
npm run build
```
Run the app
```bash
php artisan serve
```
Open:
```text
http://127.0.0.1:8000
```
---
Common Development Commands
Clear cached configuration/views/routes
```bash
php artisan optimize:clear
```
Run migrations from a fresh state
```bash
php artisan migrate:fresh --seed
```
Run tests
```bash
php artisan test
```
Format code with Laravel Pint
```bash
./vendor/bin/pint
```
On Windows PowerShell:
```powershell
vendor\bin\pint
```
---
Database Export and Restore
The current local PostgreSQL database name is:
```text
dar_iland
```
Export database
```bash
pg_dump -U dar_admin -h 127.0.0.1 -p 5432 -d dar_iland --no-owner --no-privileges -f dar_iland_full_export.sql
```
For a `postgres` user setup:
```bash
pg_dump -U postgres -h 127.0.0.1 -p 5432 -d dar_iland --no-owner --no-privileges -f dar_iland_full_export.sql
```
Restore database
Create the database:
```bash
createdb -U postgres dar_iland
```
Restore the SQL file:
```bash
psql -U postgres -d dar_iland -f dar_iland_full_export.sql
```
When the SQL export contains uploaded document records, include the matching files from `storage/app/public` in the handoff package.
---
Beta Testing Flow
Recommended beta testing order:
```text
1. Log in as DAR Staff
2. Create landowner records
3. Create parcel records
4. Create landholding records from parcel records
5. Create or link landowner user accounts
6. Encode a land transfer clearance application
7. Link transferor/transferee records
8. Upload supporting documents
9. Submit the application for review
10. Process the workflow stages
11. Generate/review Form No. 3
12. Complete/review Form No. 4
13. Generate/review endorsement/clearance outputs
14. Release/approve or deny/not approve the application
15. Confirm final-state locking
16. Test landowner portal visibility
17. Test geodetic portal visibility
18. Generate monitoring reports
19. Review audit logs
```
---
Beta Testing Checklist
Staff checks
login works
staff dashboard loads
create landowner record
create parcel record
create landholding record from a parcel record
create application
link transferor and transferee records
upload each required document type
submit application for review
open Application Actions modal
process each workflow stage
view application timeline
view requirement sidebar/checklist
Form No. 3 preview is readable and compact
Form No. 3 PDF fits one page with formal layout
Form No. 4 preview is compact
Form No. 4 PDF uses formal print styling
Recent Tax Declaration appears once
final decision locks editing/uploads
clearance/decision output is available after final action
monitoring report opens and prints
audit logs record important actions
Landowner checks
landowner dashboard loads
own applications are visible
own parcel/application status is visible
decision output is visible when available
parcel map opens for own parcel records
URL access respects landowner ownership boundaries
Geodetic checks
geodetic dashboard loads
parcel list opens
parcel detail opens
parcel map opens
parcel/reference review information is visible
workflow decision actions remain assigned to authorized staff functions
UI checks
sidebar items are readable
scope reminder appears only where useful
application review page has stable progress/sidebar behavior
document requirement sidebar remains visible and useful
modal backdrops and buttons are aligned
print/PDF pages use formal black/gray design
mobile/tablet layout is acceptable for review pages where applicable
---
Final Decision Lock Rule
When an application reaches a final status, the system locks further editing and upload actions for that application record.
Final statuses:
Released / Approved
Denied / Not Approved
Allowed final-state actions are limited to authorized viewing, output access, monitoring, audit review, and archival/reference use.
---
Deployment Notes
Recommended production `.env` values:
```env
APP_NAME="DAR-LTCMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=dar_iland
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password
```
Before deployment:
```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
For local troubleshooting after config changes:
```bash
php artisan optimize:clear
```
---
Handoff Packaging Notes
For a clean handoff ZIP:
files should extract directly into the project root
include `app/`, `database/`, `resources/`, `routes/`, `public/`, config files, Composer files, npm files, and Vite/Tailwind files
include `.env.example`
include `.env` only for trusted local teammate handoff
include `storage/app/public` files when the database references uploaded documents
name database exports using `dar_iland_full_export.sql`
use `DB_DATABASE=dar_iland` in database setup instructions
keep visible project text as DAR-LTCMS
---
Naming Notes
Use DAR-LTCMS in:
thesis manuscript
README
UI labels
login/header/dashboard text
beta tester guide
screenshots/captions
presentation slides
deployment title
Old references to DAR-iLAND may still appear in local folder/repository names until the project/repository is renamed.
---
Thesis-Safe Description
Suggested description:
> DAR-LTCMS is a web-based clearance processing and monitoring system for the Department of Agrarian Reform Negros Oriental Provincial Office. It supports DAR staff in managing landowner, parcel, landholding, source reference, document, and clearance application records. The system provides role-based access for staff, landowners, and geodetic personnel, with application status monitoring, form generation, audit logging, and report generation to improve traceability and administrative workflow visibility.
---
Current Stability Priority
For beta deployment, prioritize:
```text
1. Correct workflow
2. Role-based access
3. Final decision locking
4. Accurate document checklist
5. Printable Form No. 3 and Form No. 4 outputs
6. Landowner privacy
7. Geodetic read-oriented access
8. Audit trail completeness
9. Monitoring/report accuracy
10. UI readability
```
---
Developer Notes
Keep changes small and testable before beta.
Validate every patch with PHP syntax checks where possible.
Run `php artisan optimize:clear` after replacing Blade/PHP files.
Run `npm run build` after frontend changes.
Run `php artisan migrate` after migration patches.
Retest final-state locking after workflow changes.
Retest landowner URL access after route/controller changes.
Retest Form No. 3 and Form No. 4 after PDF or Blade changes.
