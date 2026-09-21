# Barebones Tester Handoff Guide

This guide prepares **DAR-LTCMS** for controlled user testing with an empty working dataset and only tester accounts/reference configuration.

> **Testing only:** `migrate:fresh` permanently deletes the target database contents. Never run this reset against the production DAR-LTCMS database.

The purpose is to let testers create records through the web interface instead of relying on pre-filled demo transactions.

## Barebones database state

After running the barebones reset, the system should contain:

- five active Staff tester accounts
- the required document reference list
- no Landowner demo records
- no Geodetic demo accounts
- no Parcel demo records
- no Landholding demo records
- no Source Record demo records
- no Clearance Application demo records
- no application document demo records
- no notification demo records
- no audit-log demo records

The required document list is kept because it is workflow reference/configuration data, not tester-entered transaction data.

## Staff tester accounts

The seeder creates these Staff accounts. All use the test password `password` and are for local/staging testing only.

| Name | Email |
|---|---|
| DAR Staff Tester | `staff.tester@dar-ltcms.local` |
| Jay | `jay.staff@dar-ltcms.local` |
| Miles | `miles.staff@dar-ltcms.local` |
| Vea | `vea.staff@dar-ltcms.local` |
| Lloyd | `lloyd.staff@dar-ltcms.local` |

Use `staff.tester@dar-ltcms.local` as the primary starting account unless a test specifically needs multiple Staff users.

## Reset command

From the project root:

```bash
php artisan migrate:fresh --seeder=BarebonesTesterSeeder
```

Then install/build frontend assets as needed and start Laravel:

```bash
npm ci
npm run build
php artisan serve
```

## Recommended testing flow

1. Log in as the primary Staff tester.
2. Open User / Role Management.
3. Create a Landowner account if Landowner portal testing is needed.
4. Create a Geodetic account if read-only parcel/map testing is needed.
5. Create a Landowner record and link its Landowner user account.
6. Create a Parcel record.
7. Create/link a Landholding record.
8. Optionally encode a Source Record Package/reference record.
9. Encode a Land Transfer Clearance application manually as Staff.
10. Upload/review supporting documents and fill requirement-specific metadata.
11. Process the application through the office workflow:
    - Legal Completeness Review
    - Payment / Official Receipt Recording
    - Endorsed to LTID for Verification
    - LTID Verification / Returned to Legal
    - Legal Evaluation / CSW Preparation
    - Chief Legal Final Review
    - Forwarded to PARPO II
    - PARPO II Decision Pending
12. Record one **Approved** final decision and one **Denied** final decision.
13. Confirm final-decision locking after Approved/Denied.
14. Mark a signed final output **Ready for Release**, then record **Released to Client** without changing the final decision.
15. Confirm significant actions appear in Audit Logs.
16. Confirm role-appropriate notifications.
17. Confirm Landowner privacy/isolation and post-release final-output visibility.
18. Confirm Geodetic access remains limited to parcel/reference review and explicitly scoped geometry editing.
19. Confirm Monitoring Reports and Parcel Map behavior.
20. Confirm LTC Form No. 5 output for final applications.

## Scope reminder for testers

DAR-LTCMS is a clearance generation, administrative processing, monitoring, parcel/reference review, and records-management system.

An **Approved** or **Denied** application records the final administrative clearance decision. Release of the signed output is tracked separately. Neither approval nor release automatically transfers land ownership, mutates Registry of Deeds records, or conclusively executes a legal land transfer.

Any actual ownership transfer, registry alteration, or legal mutation remains outside the automatic system scope and is subject to separate legal and administrative procedures.
