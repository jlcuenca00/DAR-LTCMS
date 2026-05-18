# Thesis Documentation Alignment Notes

Use this file to keep the thesis wording consistent with the implemented system.

## Correct system framing

Describe the system as:

- a web-based land transfer clearance processing and monitoring system
- an administrative records-management platform
- a clearance generation and decision-output system
- a parcel/reference review and monitoring support system
- a role-based DAR Negros Oriental office system

Do not describe the system as:

- an automatic ownership transfer system
- a registry mutation engine
- a replacement for official DAR legal/administrative decision-making
- a system that conclusively finalizes legal ownership transfer upon approval

## Scope and limitations wording

Recommended wording:

> The system is limited to the generation, processing, monitoring, and record management of land transfer clearance applications within the DAR Negros Oriental Provincial Office. It does not automatically execute land ownership transfer, mutate Registry of Deeds records, or finalize legal land transfer. Approval of an application within the system only records and generates the clearance result, locks the application decision, supports monitoring and reporting, and preserves audit trails. Any actual transfer of ownership or registry alteration remains subject to separate legal and administrative procedures.

## Agricultural classification wording

Recommended wording:

> The system includes agricultural classification/status fields for parcel and source-record organization. These fields support review, filtering, monitoring, and documentation. They are not treated as automatic approval gates and do not trigger land ownership transfer or registry mutation.

Use normal feature labels:

- Land Transfer Clearance
- Clearance Applications
- Parcel Records
- Landowner Records
- Landholdings
- Monitoring Reports
- Parcel Map Viewer

Avoid over-labeling every screen as “agricultural.” The DAR clearance context already concerns agricultural land records, while the system remains named and presented as a Land Transfer Clearance and Monitoring System.

## Role access summary

### DAR Staff

Staff manually encode and process records/applications, upload and review documents, generate clearance outputs, manage users, monitor reports, and view audit logs.

### Landowner

Landowners only view their own linked parcel/application/status/decision records. They do not create applications and must not access records of other landowners.

### Geodetic Personnel

Geodetic users review parcel/reference/map information in a limited, read-only capacity. They are not approval users and do not process clearance decisions.

## Auditability summary

The system supports traceability through:

- timestamped audit logs
- actor-based action recording
- application timeline/status history
- final decision lock enforcement
- document upload/removal logging
- clearance generation logging
- user creation/update logging
- preservation of final decision records

## Chapter/documentation areas to align

Check these thesis sections for consistent wording:

- System title
- Abstract
- Introduction
- Statement of the Problem
- Objectives of the Study
- Scope and Limitations
- Significance of the Study
- Conceptual Framework
- System Features
- Use Case Diagram descriptions
- Activity Diagram descriptions
- Sequence Diagram descriptions
- ERD/database discussion
- Data Dictionary
- Testing and Evaluation
- Conclusion and Recommendations

## Diagram modeling rule

Never model approved clearance as directly changing parcel ownership.

Approved clearance should only lead to:

- clearance result generation
- final status recording
- application locking/finalization
- monitoring/reporting update
- audit logging
- archival/view-only access

Actual land ownership transfer, legal mutation, and registry alteration must remain outside the automatic system flow.
