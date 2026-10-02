# DAR-LTCMS Citizen's Charter Workflow Baseline

This implementation aligns the administrative Land Transfer Clearance workflow with the DAR Citizen's Charter process for A.O. No. 4, Series of 2021 while preserving the approved DAR-LTCMS thesis scope.

## Processing flow

1. Application encoded / Legal completeness review
2. When any issue prevents continued processing or approval, Legal Clearance Staff records **Request Compliance / Action Required**
3. The application enters **Compliance Required** without being finalized; the compliance notice preserves the category, instructions/items needed, requester, timestamp, and the workflow stage that must resume
4. The linked Landowner receives an application notification and persistent Action Required alert
5. After the applicant addresses the issue through the normal DAR office process, Legal Clearance Staff records **Compliance Resolved** and the same application resumes its saved prior stage
6. Issue/record Payment Order after documentary completeness
7. Record Official Receipt after the external DAR cashier transaction
8. Endorse to LTID for verification
9. LTID verification / LTC Form No. 4
10. Return to Legal Division
11. Legal evaluation, Completed Staff Work (CSW), and clearance preparation
12. Chief Legal review (tracked by Legal Clearance Staff)
13. Forward to PARPO II (tracked by Legal Clearance Staff)
14. PARPO II Approved decision received and recorded by Legal Clearance Staff
15. Generate and preserve the immutable GRANTED LTC Form No. 5 output, including the display metadata needed to reproduce the issued form without later live-record reads
16. Record return of the signed output to Legal / Ready for Release
17. Record actual release to the client or authorized representative and logbook/CSM reference

The compliance loop may be used more than once and from any supported open workflow stage. It does not create a replacement application.

## Final-decision rule

**Approved** is the only current final application decision state. Once Approved is recorded, application edits, supporting-document changes, LTC Form No. 4 changes, parcel-link changes, compliance actions, and other decision-record mutations are locked.

Older **Not Approved**, **Denied**, and application-level **Released** values remain readable only as historical final records. DAR-LTCMS does not provide a current Staff action, route, or workflow transition that creates a new Not Approved / Denied decision.

The later release status tracks administrative delivery only:

- **Not Ready**
- **Ready for Release**
- **Released to Client**

Changing the release status never changes the final Approved decision.

## Compliance rule

A deficiency or unresolved issue is an **open compliance condition**, not a final negative decision.

Each compliance notice records:

- structured category;
- an unrestricted **Other** category with required custom text;
- detailed Staff instructions;
- optional items/documents to bring or provide;
- the workflow stage to resume;
- requester and request timestamp; and
- resolver, resolution timestamp, and optional resolution note.

Landowners remain read-only participants. They do not create applications or upload compliance documents themselves; Legal Clearance Staff records/uploads documents after normal office submission and review.

## Scope boundary

DAR-LTCMS generates, records, and monitors the clearance decision. It does not automatically transfer land ownership, create a transferee landholding, alter Registry of Deeds records, or conclusively execute a legal land transfer. All validation checks are assistive and remain subject to authorized DAR legal and administrative review.
