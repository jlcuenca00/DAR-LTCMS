# Thesis Documentation Alignment Notes

Use this file together with `docs/FINAL_SYSTEM_BASELINE.md` to keep the thesis wording consistent with the implemented DAR-LTCMS system.

## Correct system framing

Describe DAR-LTCMS as:

- a web-based Land Transfer Clearance processing and monitoring system
- an administrative records-management platform
- a clearance generation and decision-support system
- a Parcel/reference review and monitoring support system
- a role-based DAR Negros Oriental Provincial Office system

Do **not** describe it as:

- an automatic ownership transfer system
- a registry mutation engine
- a replacement for official DAR/legal/administrative decision-making
- a platform that conclusively finalizes legal ownership transfer when a clearance is Released

## Scope and limitations wording

Recommended wording:

> DAR-LTCMS is limited to the generation, processing, monitoring, and records management of Land Transfer Clearance applications within the DAR Negros Oriental Provincial Office. It does not automatically execute land ownership transfer, mutate Registry of Deeds records, or conclusively finalize legal land transfer. A Released clearance records and generates the administrative clearance result, locks the application decision, supports monitoring/reporting, and preserves audit trails. Any actual ownership transfer or registry alteration remains subject to separate legal and administrative procedures outside the system's automatic operations.

## Current application workflow terminology

Use these current user-facing stages in thesis descriptions and diagrams:

1. Pending Review by Legal Officer
2. Endorsed to LTI Division
3. Endorsed to Chief Legal
4. Endorsed to PARPO II
5. For Releasing
6. Released

A separate application may end in **Denied**.

Use **Released** and **Denied** as the final user-facing states. Historical/internal values such as `approved` and `not_approved` may exist for backward compatibility, but they should not be presented as the current workflow labels in final thesis figures/screenshots.

## Final decision rule

Once an application is Released or Denied:

- editing is locked;
- supporting-document changes are locked;
- backend mutation attempts are rejected;
- authorized viewing/monitoring/reporting remains available; and
- final decision/output/audit history is preserved.

## Agricultural classification wording

Recommended wording:

> The system includes agricultural classification/status fields for Parcel and source-record organization. These fields support review, filtering, monitoring, and documentation. They are assistive administrative data and are not automatic legal approval gates or ownership-transfer triggers.

Use normal feature labels such as:

- Land Transfer Clearance
- Clearance Applications
- Parcel Records
- Landowner Records
- Landholdings
- Monitoring Reports
- Parcel Map

Avoid over-labeling every screen as “agricultural.” The approved system scope already concerns DAR agricultural land transfer clearance processing.

## Role access summary

### DAR Staff

Staff manually encode applications; manage Landowner, Parcel, Landholding, Source/Reference, and application records; upload/review supporting documents; process the office workflow; generate/view clearance outputs and reports; manage authorized users; and review Audit Logs.

### Landowner

Landowners do not create applications. They may view only their own linked Parcel/Landholding/Application/status/final-output information and must never access another Landowner's records.

### Geodetic Personnel

Geodetic users have limited/read-only Parcel/reference/map review access. They are not primary clearance decision users and do not broadly edit ownership/application records.

## LTC Form No. 5 wording

Form No. 5 is the final administrative clearance output generated from the recorded application decision/data.

Use these implementation facts when describing it:

- Released → GRANTED output
- Denied → DENIED output
- annual LTC number sequence and page reference
- linked Parcel title/Tax Declaration/lot/survey references
- combined recorded area
- signatory: `ENGR. MANUEL M. GALON, JR., OIC PARPO II`
- notarial Doc/Page/Book/Series information when encoded
- 8.5 x 13 inch print/PDF format

Do not describe Form No. 5 generation as registry mutation or automatic legal ownership transfer.

## Auditability summary

The system supports traceability through:

- timestamped actor-based Audit Logs
- application timeline/status history
- final decision lock enforcement
- document upload/removal logging
- clearance generation/finalization logging
- user administration logging
- preservation of final decision records
- protected administrative file delivery

## Chapter/documentation areas to align

Check these thesis sections for consistent wording:

- System title
- Abstract
- Introduction
- Statement of the Problem
- Objectives of the Study
- Scope and Limitations
- Significance of the Study
- Conceptual/Theoretical Framework
- System Features/Modules
- Use Case Diagram descriptions
- Activity Diagram descriptions
- Sequence Diagram descriptions
- DFD descriptions
- ERD/database discussion
- Data Dictionary
- Testing and Evaluation
- Conclusion and Recommendations

## Diagram modeling rule

Never model **Released/GRANTED clearance → change Parcel owner**.

A final clearance may lead only to system-scope actions such as:

- clearance result generation
- final status recording
- application locking/finalization
- monitoring/reporting update
- audit logging
- archival/view-only access

Actual land ownership transfer, legal mutation, and registry alteration remain outside the automatic DAR-LTCMS flow.
