# DAR-LTCMS Final System Baseline

This file is the canonical implementation reference for the final DAR-LTCMS thesis/system baseline.

## System identity

**DAR-LTCMS — Department of Agrarian Reform Land Transfer Clearance and Monitoring System**

Deployment scope: **DAR Negros Oriental Provincial Office**.

System type:

- web-based administrative processing system
- clearance generation and monitoring system
- records-management platform
- decision-support platform

DAR-LTCMS is **not** an automatic land ownership transfer system and is **not** a registry mutation engine.

## Core scope

The implemented system covers:

- Landowner records
- Parcel records
- Landholding records
- Staff-encoded Land Transfer Clearance applications
- supporting-document upload/review
- requirement-specific document data capture
- Source / Reference Records
- application workflow/status monitoring
- LTC forms and final clearance output
- role-based access control
- notifications
- audit logging
- Parcel Map review
- monitoring/report generation
- authorized user administration

## Critical legal/operational boundary

A Released clearance means DAR-LTCMS has recorded and generated the administrative clearance result.

It does **not** mean the platform has:

- transferred legal ownership to another person;
- rewritten Registry of Deeds records;
- mutated a Parcel owner automatically;
- conclusively completed the legal land transfer; or
- replaced official DAR/legal/registry procedures.

Any real ownership transfer or registry alteration remains outside automatic system operations and is subject to separate legal and administrative procedures.

## User roles

### DAR Staff

Staff:

- manually encode applications;
- manage Landowner, Parcel, Landholding, Source/Reference, and application records;
- upload/review supporting requirements;
- process applications through the office workflow;
- generate/view reports and clearance outputs;
- review audit logs; and
- manage authorized system accounts.

### Landowner

Landowners:

- do not create applications;
- may view only their own linked Parcel/Landholding/Application information;
- may view their own application status/final output when authorized; and
- must never access another Landowner's records.

### Geodetic Personnel

Geodetic users:

- have limited/read-only Parcel/reference/map access;
- are not primary clearance decision users;
- do not broadly edit ownership/application records; and
- do not receive Staff-level administrative access.

## Current application workflow

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

An application may separately end in **Denied**.

Current final user-facing states:

- `Released`
- `Denied`

Legacy stored values `approved` and `not_approved` may be recognized for backward compatibility but display as Released/Denied and must not be used as the current workflow terminology in thesis screenshots/diagrams.

## Final-decision freeze

After Released or Denied:

- editing is locked;
- supporting-document upload/removal is locked;
- backend mutation requests are rejected;
- UI reflects the locked final state;
- final output remains viewable to authorized users;
- reporting/monitoring remains available;
- audit history is preserved; and
- only authorized viewing/archival actions remain appropriate.

## Supporting documents and requirement data

Document/requirement fields are requirement-specific. Current examples include:

- title/TD/receipt/certificate/document number as applicable
- Date issued
- lot/Parcel reference where applicable
- names appearing in the requirement
- transfer instrument title/type, area, transferor/s, and transferee/s
- notarizer/lawyer name
- Date notarized
- notarial Document No., Page No., Book No., Series
- MARPO/LTC Form No. 2 review checks when applicable
- Staff verification notes

File upload and validation/data capture are assistive administrative tools; they are not final legal authority.

## LTC Form No. 5 baseline

Final Form No. 5 behavior includes:

- annual LTC sequence
- stored LTC page number
- example appearance: `1803-2026-0043 (7)`
- all linked Parcel title/Tax Declaration/lot/survey references as applicable
- combined recorded area
- `GRANTED` for Released
- `DENIED` for Denied
- signatory: `ENGR. MANUEL M. GALON, JR., OIC PARPO II`
- notarial Doc No., Page No., Book No., Series when encoded
- 8.5 x 13 inch print/PDF layout
- no printed signature/stamp presented as an executed signature

Form No. 5 is a generated clearance output only; it does not mutate Parcel ownership or registry records.

## Security and auditability baseline

The system preserves:

- strict RBAC
- Landowner record isolation
- limited Geodetic access
- protected administrative uploads/source scans
- no public `storage` symlink requirement for sensitive records
- final-decision locking
- timestamped actor-based audit logs
- application/record traceability
- production security/configuration checks
- production dependency security audits
- data-integrity scanning

## Monitoring/reporting baseline

Monitoring Reports use administrative status/output data and may filter by date, status, and municipality.

Released/Denied output totals and **Recorded Output Area** are monitoring/reporting metrics only; they do not represent registry mutation or conclusively completed legal ownership transfer.

## Map baseline

Parcel Map functions are for geographic review/reference/monitoring.

- Staff: broader authorized Parcel view
- Landowner: own linked Parcel view only
- Geodetic: limited/read-only Parcel/reference view

Map interaction must not automatically change Parcel ownership.

## Production/release baseline

Repository default/production branch: `main`.

Merging to `main` triggers the CloudPanel deployment workflow.

Before the final `v1.0.0` tag:

1. automatic test/security gates must be green;
2. production database backup must exist;
3. production private-file backup must exist;
4. `php artisan dar:release-check` must pass on the live server;
5. `.release-commit` must match the intended `main` commit; and
6. the post-deployment smoke test must pass.

Until those live production checks are complete, the project should be described as a **release candidate**, not a completed `v1.0.0` release.

## Thesis/diagram rule

Never draw a flow where Released/GRANTED clearance directly changes Parcel ownership.

A final clearance may lead to:

- clearance generation
- final status recording
- application locking
- monitoring/reporting
- audit logging
- archival/view-only access

Actual land ownership transfer and registry mutation remain outside DAR-LTCMS automatic scope.
