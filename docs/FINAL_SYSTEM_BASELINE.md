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

An **Approved** application means DAR-LTCMS has recorded the current final administrative clearance decision. Issues that prevent approval remain open through the **Compliance Required** workflow. Older Not Approved / Denied records remain historical and read-only. Release of the signed output is tracked separately.

It does **not** mean the platform has:

- transferred legal ownership to another person;
- rewritten Registry of Deeds records;
- mutated a Parcel owner automatically;
- conclusively completed the legal land transfer; or
- replaced official DAR/legal/registry procedures.

Any real ownership transfer or registry alteration remains outside automatic system operations and is subject to separate legal and administrative procedures.

## User roles

### Legal Clearance Staff

The internal DAR-LTCMS operator role is limited to the Legal Division personnel assigned to clearance entry, processing records, and exit/release tracking. The existing database role value remains `staff` for compatibility.

Legal Clearance Staff:

- manually encode applications;
- manage Landowner, Parcel, Landholding, Source/Reference, and application records;
- upload/review supporting requirements;
- record administrative workflow movements and results received from other DAR offices;
- record and resolve structured Compliance Required notices while preserving the same application and its resume stage;
- record the official PARPO II Approved decision without impersonating the decision authority;
- generate/view reports and clearance outputs;
- review audit logs; and
- manage authorized system accounts.

LTID personnel, Chief Legal, PARPO II, the cashier, and other DAR offices are tracked as **administrative authorities/stages**, not DAR-LTCMS user accounts. The logged-in Legal Clearance Staff user is the system recorder of those external office actions.

### Landowner

Landowners:

- do not create applications;
- may view only their own linked Parcel/Landholding/Application information;
- may view persistent Action Required / Compliance Required notices tied to their own applications;
- may view their own application status/final output when authorized; and
- must never access another Landowner's records.

### Geodetic Personnel

Geodetic users:

- have limited Parcel/reference/map review access;
- may edit only explicitly scoped parcel geometry through the controlled Geodetic geometry workflow;
- are not primary clearance decision users;
- do not edit ownership/application decision records; and
- do not receive Staff-level administrative access.

## Current application workflow

1. Legal Completeness Review
2. Payment / Official Receipt Recording
3. With LTID for Verification
4. Returned to Legal Division
5. Legal Evaluation / CSW Preparation
6. With Chief Legal for Review
7. With PARPO II for Decision
8. PARPO II Decision Ready to Record
9. Approved — final decision

The separate delivery lifecycle then records signed Form No. 5 / Ready for Release and Released to Client. Any supported open stage can enter Compliance Required and return to its saved stage after resolution.

The only current final application decision state is:

- `Approved`

**Compliance Required** is an open corrective state, not a negative final decision. It may be used repeatedly when an issue must be corrected, clarified, amended, or supplied before processing continues.

Release is a separate administrative delivery status. A signed Approved output may be marked **Ready for Release** and later **Released to Client** without changing the final Approved decision.

Legacy stored values `not_approved`, `denied`, application-level `released`, `pending_review`, and `draft` remain recognized only for historical compatibility. New Not Approved / Denied decisions cannot be created by the current workflow.

## Final-decision freeze

After Approved (and for preserved historical final records):

- editing is locked;
- supporting-document upload/removal is locked;
- backend mutation requests are rejected;
- UI reflects the locked final state;
- final output remains viewable to authorized users according to release rules;
- reporting/monitoring remains available;
- audit history is preserved; and
- only authorized release, viewing, monitoring, reporting, and archival actions remain appropriate.

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
- `GRANTED` for current Approved decisions
- preserved historical `DENIED` output only for older negative final records
- signatory: recorded PARPO II decision officer/signatory preserved in the immutable final-decision metadata
- notarial Doc No., Page No., Book No., Series when encoded
- payment/notarial/transfer-document display values are captured into the immutable Form No. 5 snapshot at final decision; later rendering does not depend on mutable live application/document fields
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
- timestamped actor-based audit logs, with Activity and Login History separated
- application/record traceability
- production security/configuration checks
- production dependency security audits
- data-integrity scanning

## Monitoring/reporting baseline

Monitoring Reports use administrative status/output data and may filter by date, status, and municipality.

Approved output totals, release totals, preserved historical negative-output totals, and **Recorded Output Area** are monitoring/reporting metrics only; they do not represent registry mutation or conclusively completed legal ownership transfer.

## Map baseline

Parcel Map functions are for geographic review/reference/monitoring.

- Staff: broader authorized Parcel view
- Landowner: own linked Parcel view only
- Geodetic: limited Parcel/reference view with controlled geometry-only editing

Map interaction must not automatically change Parcel ownership.

## Production/release baseline

Repository default/production branch: `main`.

Merging to `main` triggers the CloudPanel deployment workflow.

Before the final `v1.0.0` tag:

1. strict SSH host verification must use an independently trusted production host key;
2. automatic test/security gates must be green;
3. production database backup must exist;
4. production private-file backup must exist;
5. `php artisan dar:release-check` must pass on the live server;
6. `.release-commit` must match the intended `main` commit; and
7. the post-deployment smoke test must pass.

See [release preparation](RELEASE_PREPARATION.md) for the operational checks.

## Thesis/diagram rule

Never draw a flow where Released/GRANTED clearance directly changes Parcel ownership.

For auditability, DAR-LTCMS distinguishes the **official administrative authority/action** from the **Legal Clearance Staff user who records it**. Final decisions store the PARPO II authority, decision officer/signatory, official decision date, recorder, and recording timestamp.

A final clearance may lead to:

- clearance generation
- final status recording
- application locking
- monitoring/reporting
- audit logging
- archival/view-only access

Actual land ownership transfer and registry mutation remain outside DAR-LTCMS automatic scope.
