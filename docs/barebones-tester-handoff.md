# Barebones Tester Handoff Guide

This guide prepares the DAR-LTCMS / DAR-iLAND system for user testing with an empty working database and only one starting staff account.

The purpose is to let testers create data through the system UI instead of testing pre-filled demo records.

## Barebones database state

After running the barebones reset, the system should contain:

- one active staff account
- required document reference records
- no landowner demo records
- no geodetic demo records
- no parcel demo records
- no landholding demo records
- no source record demo records
- no clearance application demo records
- no application document demo records
- no audit log demo records
- no notification demo records

The required document list is kept because it is reference/configuration data used by the clearance application workflow, not tester-filled demo data.

## Starting staff account

Use this account only to begin the test session:

```text
Email: staff.tester@dar-ltcms.local
Password: password
Role: Staff
```

After logging in, the tester may create additional staff, landowner, or geodetic accounts through Staff User / Role Management if those roles need to be tested.

## Reset command

From the project root, run:

```bash
php artisan migrate:fresh --seeder=BarebonesTesterSeeder
```

Then build assets:

```bash
npm run build
```

Then start the system:

```bash
php artisan serve
```

## What the tester should do through the system

The tester should not directly edit the database. The tester should create and review data using the web interface.

Recommended testing flow:

1. Log in as the staff tester account.
2. Open User / Role Management.
3. Create a landowner user account if landowner portal testing is needed.
4. Create a geodetic user account if geodetic portal testing is needed.
5. Create a landowner record.
6. Link the landowner user account to the landowner record.
7. Create a parcel record.
8. Create or link a landholding record for the landowner and parcel.
9. Optionally create a source record package/reference record.
10. Encode a land transfer clearance application manually as staff.
11. Upload supporting documents and encode document metadata/indexing fields.
12. Submit the application for review.
13. Test approval or not-approved decision behavior.
14. Confirm final decision locking.
15. Confirm audit log entries.
16. Confirm notification bell/dropdown behavior.
17. Confirm landowner privacy filtering.
18. Confirm geodetic read-only access.
19. Confirm monitoring report output.
20. Confirm parcel map visibility per role.

## Scope reminder for testers

The system is a clearance generation, application processing, monitoring, parcel/reference review, and records-management system only.

Approval of a clearance application does not automatically transfer ownership, does not mutate Registry of Deeds records, and does not legally finalize land transfer by itself.

Any actual ownership transfer, registry alteration, or legal mutation remains outside the automatic system scope and belongs to separate legal and administrative procedures.
