# DAR-LTCMS Citizen's Charter Workflow Baseline

This implementation aligns the administrative Land Transfer Clearance workflow with the DAR Citizen's Charter process for A.O. No. 4, Series of 2021 while preserving the approved DAR-LTCMS thesis scope.

## Processing flow

1. Application encoded / Legal completeness review
2. Return for compliance when incomplete, without recording a final denial
3. Issue/record Payment Order after documentary completeness
4. Record Official Receipt after the external DAR cashier transaction
5. Endorse to LTID for verification
6. LTID verification / LTC Form No. 4
7. Return to Legal Division
8. Legal evaluation, Completed Staff Work (CSW), and clearance preparation
9. Chief Legal final review
10. Forward to PARPO II
11. PARPO II final decision: Approved or Denied
12. Generate and preserve the immutable LTC Form No. 5 decision output
13. Record return of the signed output to Legal / Ready for Release
14. Record actual release to the client or authorized representative and logbook/CSM reference

## Final-decision rule

`approved` and `denied` are final application decision states. Once either state is recorded, application edits, supporting-document changes, LTC Form No. 4 changes, parcel-link changes, and other decision-record mutations are locked.

The later `release_status` tracks administrative delivery only:

- `not_ready`
- `ready_for_release`
- `released`

Changing the release status never changes the final PARPO II decision.

## Scope boundary

DAR-LTCMS generates, records, and monitors the clearance decision. It does not automatically transfer land ownership, create a transferee landholding, alter Registry of Deeds records, or conclusively execute a legal land transfer. All validation checks are assistive and remain subject to authorized DAR legal and administrative review.
