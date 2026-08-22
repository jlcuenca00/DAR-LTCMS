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
    - Pending Review by Legal Officer
    - Endorsed to LTI Division
    - Endorsed to Chief Legal
    - Endorsed to PARPO II
    - For Releasing
12. Test one **Released** final outcome and one **Denied** final outcome.
13. Confirm final-decision locking after Released/Denied.
14. Confirm significant actions appear in Audit Logs.
15. Confirm role-appropriate notifications.
16. Confirm Landowner privacy/isolation.
17. Confirm Geodetic access remains limited/read-only.
18. Confirm Monitoring Reports and Parcel Map behavior.
19. Confirm LTC Form No. 5 output for final applications.

## Scope reminder for testers

DAR-LTCMS is a clearance generation, administrative processing, monitoring, parcel/reference review, and records-management system.

A **Released** clearance records and generates the administrative clearance result only. It does not automatically transfer land ownership, mutate Registry of Deeds records, or conclusively execute a legal land transfer.

Any actual ownership transfer, registry alteration, or legal mutation remains outside the automatic system scope and is subject to separate legal and administrative procedures.
