# DAR-LTCMS

**Department of Agrarian Reform – Land Transfer Clearance and Monitoring System**

DAR-LTCMS is a web-based administrative processing, records-management, and monitoring platform developed for the **Department of Agrarian Reform (DAR) Negros Oriental Provincial Office**. It supports the processing of land transfer clearance applications, management of related land records, supporting-document review, clearance generation, monitoring, reporting, and auditability.

## Live System

**Production site:** https://darltcms.me

The deployed system is intended for authorized DAR personnel and approved stakeholders. Access to system functions and records is controlled according to user role and record ownership.

## Project Overview

DAR-LTCMS centralizes the records and activities involved in office-based land transfer clearance processing, including:

- landowner record management
- parcel record management
- landholding record management
- land transfer clearance application processing
- supporting-document upload and review
- application status monitoring
- role-based access control
- notifications
- audit logging
- map-based parcel review
- monitoring and report generation
- LTC form and clearance output generation

The platform supports administrative processing and decision support. Clearance approval or release records the DAR clearance result and makes the corresponding output available for authorized viewing, printing, monitoring, and archival purposes. Any subsequent legal ownership transfer or registry alteration remains subject to the appropriate legal and administrative procedures outside the system's automatic operations.

## User Roles

### DAR Staff

DAR Staff are the primary system operators. They can:

- encode and manage landowner, parcel, and landholding records
- manually encode land transfer clearance applications
- upload and review supporting documents
- process applications through the authorized workflow
- generate LTC forms and clearance outputs
- monitor application status and processing history
- generate reports
- review audit trails and significant system activities

### Landowner

Landowner accounts provide restricted access to records linked to the authenticated landowner. They can:

- view their own linked parcel and landholding information
- view their own clearance application status
- view final decision information and available clearance outputs associated with their records

Landowner access is isolated from records belonging to other landowners.

### Geodetic Personnel

Geodetic personnel have limited review-oriented access. They can:

- review parcel and reference information
- inspect map-based parcel geometry and location data
- view information relevant to parcel verification

Their access remains limited and does not provide general application approval or ownership-editing authority.

## Application Workflow

The current office-oriented workflow follows these processing stages:

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

An application may also reach a final **Denied / Not Approved** outcome when required by the authorized review decision.

Once an application reaches a final decision state, editing and document-upload actions are locked. Authorized users may continue to view the record, clearance output, audit history, and monitoring information.

## Core Modules

| Module | Purpose |
|---|---|
| Dashboard | Role-based overview of applications, records, and monitoring information |
| Landowner Records | Maintain landowner profiles and linked accounts |
| Parcel Records | Store parcel details, title/tax declaration references, area, classification, and map geometry |
| Landholding Records | Maintain parcel-based landholding relationships |
| Source / Reference Records | Preserve supporting reference information used during review |
| Clearance Applications | Encode, review, endorse, release, deny, and monitor applications |
| Supporting Documents | Upload, view, index, and review application-related documents |
| LTC Forms and Outputs | Generate office forms, printable records, and final clearance outputs |
| Parcel Map | Review mapped agricultural parcel information |
| Notifications | Surface important application events to authorized users |
| Monitoring and Reports | Produce office-level monitoring summaries and reports |
| Audit Logs | Record significant actions with actor, timestamp, and record context |
| Administration | Manage authorized system accounts and role assignments |

## LTC Forms and Outputs

DAR-LTCMS supports the LTC-related forms used in the application workflow, including:

- **LTC Form No. 1** – application/data form
- **LTC Form No. 2** – acknowledgment-related form
- **LTC Form No. 3** – acknowledgment receipt / printable application output
- **LTC Form No. 4** – review checklist
- **LTC Form No. 5** – official Land Transfer Clearance certification/output

Final clearance outputs are generated from the application's recorded decision and supporting data and remain available to authorized users after finalization.

## Security, Integrity, and Auditability

DAR-LTCMS prioritizes government-grade traceability and controlled access through:

- strict role-based access control
- landowner record isolation
- limited geodetic access
- staff-controlled application encoding and processing
- protected supporting documents and clearance outputs
- final-decision locking
- timestamped audit logs
- actor-based activity tracking
- preserved application and decision history
- server-side authorization checks for sensitive actions
- controlled account administration

These controls are designed to preserve accountability, data integrity, and traceability throughout clearance processing.

## Technology Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 |
| Server Language | PHP 8.4 |
| Database | PostgreSQL |
| Authentication | Laravel Breeze |
| Frontend | Blade, Tailwind CSS, Vite |
| Mapping | Leaflet |
| Package Management | Composer and npm |
| Deployment | Linux server with CloudPanel |

## Production and Operations

The application is deployed through the project's protected `main` branch and production deployment workflow. Production configuration, database credentials, environment files, backup credentials, and other operational secrets are intentionally excluded from this repository.

The deployed environment includes controlled production backups and recovery procedures for application data and uploaded files.

## System Scope

DAR-LTCMS is designed specifically for the **DAR Negros Oriental Provincial Office** and for agricultural land transfer clearance processing within the approved project scope.

System validation and monitoring features assist authorized personnel in reviewing encoded records and application information. Final administrative and legal determinations remain with the appropriate DAR personnel and established procedures.

## Project Status

**Status:** Live deployment with controlled ongoing development and refinement.

Current work focuses on production stability, responsive user interfaces, clearance-output accuracy, performance, security, auditability, and documentation consistency.

## Academic Context

DAR-LTCMS was developed as a thesis/capstone project and is being evaluated as a web-based administrative processing and monitoring solution for the DAR Negros Oriental Provincial Office.
