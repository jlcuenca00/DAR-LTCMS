# Final Manual Testing Checklist

Use this checklist for final UAT/defense verification in a controlled local or staging environment before the production `v1.0.0` gate.

> **Do not run `migrate:fresh`, demo seeders, or destructive test setup commands on production.** Production release validation uses `docs/RELEASE_PREPARATION.md` and `php artisan dar:release-check`.

## 1. Test setup and baseline

- [ ] Run `composer install`
- [ ] Run `npm ci`
- [ ] Confirm the test `.env` uses PostgreSQL database `dar_iland`
- [ ] Prepare the intended local/staging test dataset
- [ ] Run `npm run build`
- [ ] Run `php artisan test`

Expected: build and automated tests pass before manual UAT.

## 2. Login and role routing

- [ ] Staff can log in
- [ ] Landowner A can log in
- [ ] Landowner B can log in
- [ ] Geodetic user can log in
- [ ] Inactive account cannot continue using the system
- [ ] Each role lands on the correct dashboard

Expected: role-based dashboards/navigation are correct.

## 3. Staff dashboard and navigation

- [ ] Staff Dashboard loads
- [ ] Quick actions open the correct modules
- [ ] Sidebar active states are correct
- [ ] User Management remains an administrative function
- [ ] Logout works

## 4. Staff application encoding and document review

- [ ] Staff can open Clearance Applications
- [ ] Staff can manually encode an application
- [ ] Staff can encode/select multiple transferor/s and transferee/s where applicable
- [ ] Staff can link the relevant Parcel record/s
- [ ] Staff can upload/review supporting documents
- [ ] Requirement-specific data fields display correctly
- [ ] Date issued is available where applicable
- [ ] Transfer-instrument fields display for deeds/instruments
- [ ] Notarial fields display for notarized requirements
- [ ] Staff can submit/process the application through the workflow

Expected: document data capture supports administrative review only; it is not automatic legal verification.

## 5. Office workflow

Verify a positive-path application moves through:

- [ ] Legal Completeness Review
- [ ] Payment / Official Receipt Recording
- [ ] Endorsed to LTID for Verification
- [ ] LTID Verification / Returned to Legal
- [ ] Legal Evaluation / CSW Preparation
- [ ] Chief Legal Final Review
- [ ] Forwarded to PARPO II
- [ ] PARPO II Decision Pending
- [ ] Approved final decision
- [ ] Ready for Release
- [ ] Released to Client

Also verify a separate application can receive:

- [ ] Not Approved final decision

Expected: **Approved** and **Not Approved** are the final application decisions. Ready for Release and Released to Client are separate administrative delivery statuses.

## 6. Final-decision lock

Test one Approved and one Not Approved application.

- [ ] Edit controls are hidden/disabled after finalization
- [ ] Supporting-document upload/removal is locked
- [ ] Backend rejects post-final substantive application mutations
- [ ] Backend rejects post-final upload/removal attempts
- [ ] UI clearly shows the final/locked decision state
- [ ] Authorized release tracking, viewing, reporting, audit, and archival access remains available
- [ ] Marking Ready for Release does not change Approved/Not Approved
- [ ] Recording Released to Client does not change Approved/Not Approved

Expected: final decision records are preserved and protected while delivery is tracked separately.

## 7. LTC Form No. 5

For Approved and Not Approved records:

- [ ] Browser output opens
- [ ] Direct PDF output opens/downloads
- [ ] 8.5 x 13 inch page format is correct
- [ ] LTC number uses the annual sequence and stored page value
- [ ] Approved record prints GRANTED
- [ ] Not Approved record prints DENIED
- [ ] All linked Parcel title/TD/lot/survey references are correct
- [ ] Combined recorded area is correct
- [ ] Signatory is `ENGR. MANUEL M. GALON, JR., OIC PARPO II`
- [ ] Notarial Doc/Page/Book/Series details appear when encoded
- [ ] No printed signature/stamp placeholder is presented as an executed signature
- [ ] Output wording does not claim that legal ownership or registry records were automatically changed
- [ ] Landowner access to the final output is unavailable until actual client release is recorded

## 8. Landowner privacy

Using two different Landowner accounts:

- [ ] Landowner A sees only A-linked Parcel/Application records
- [ ] Landowner A cannot open B's records by direct URL
- [ ] Landowner B sees only B-linked Parcel/Application records
- [ ] Landowner B cannot open A's records by direct URL
- [ ] Landowner cannot create a Clearance Application
- [ ] Own final output is visible only when tied to the authenticated Landowner
- [ ] Parcel Map remains privacy-filtered

Expected: Landowners never access records belonging to another Landowner.

## 9. Geodetic limited-access verification

- [ ] Geodetic dashboard loads
- [ ] Geodetic can review allowed Parcel/reference information
- [ ] Geodetic can use the Parcel Map for authorized parcel/reference review
- [ ] Geodetic cannot process/release/deny applications
- [ ] Geodetic cannot upload application supporting documents
- [ ] Geodetic can edit only explicitly scoped parcel geometry and cannot broadly edit ownership/application records
- [ ] Geodetic cannot access Staff User Management

Expected: Geodetic access remains limited to parcel/reference review plus explicitly scoped, versioned geometry editing; ownership/application decision data remains protected.

## 10. Landowner Records

- [ ] Staff can create/edit Landowner records using the current fields
- [ ] First/Middle/Last/Suffix fields behave correctly
- [ ] Contact number, address, municipality, barangay, and province save correctly
- [ ] Linked Landowner user account behaves correctly
- [ ] One user is not incorrectly linked to multiple Landowner records

## 11. Parcel and Landholding Records

- [ ] Staff Parcel Records page loads
- [ ] Search/filter works
- [ ] Agricultural classification/status uses the current labels/controls
- [ ] Parcel area values are consistent
- [ ] Landholding links point to the intended Landowner and Parcel
- [ ] Staff can manage authorized record fields
- [ ] Landowner/Geodetic visibility restrictions remain correct

Expected: these modules manage administrative records; clearance finalization does not automatically mutate legal ownership.

## 12. Source / Reference Records

- [ ] Source Records page loads
- [ ] Encode Source Record Package works
- [ ] Source scope/provenance is visible
- [ ] Reference file upload/view works through protected delivery
- [ ] Import template/preview works when used
- [ ] Link/create Parcel and Landowner actions work only for authorized Staff
- [ ] Geodetic access remains limited/read-only where exposed

## 13. Parcel Map

- [ ] Staff map loads authorized broad Parcel data
- [ ] Municipality/barangay filtering works
- [ ] Parcel hover/click/details behavior works
- [ ] Geodetic map remains read-only
- [ ] Landowner map includes only own linked Parcels
- [ ] Geometry display is usable and non-overlapping for the test data

Expected: map features support review/reference only and do not mutate ownership.

## 14. Monitoring Reports

- [ ] Monitoring dashboard loads
- [ ] Date, status, and municipality filters work consistently
- [ ] Workflow status breakdown uses current labels
- [ ] Approved and Not Approved output totals and release tracking are correct
- [ ] Recorded Output Area is labeled as an administrative output metric
- [ ] Printable report opens
- [ ] Scope/limitation wording is visible
- [ ] Report does not claim legal transfer completion

## 15. Audit Logs

- [ ] Audit Log Viewer loads
- [ ] Filters work
- [ ] Actor and timestamp are visible
- [ ] Application/record context is traceable
- [ ] Important actions are logged, including application creation/processing/finalization, document changes, clearance generation, and user administration
- [ ] Final decision records remain traceable after locking

## 16. Notifications

- [ ] Notification bell/panel works
- [ ] Full notification archive opens
- [ ] Users only see notifications intended for them
- [ ] Staff notifications distinguish Approved/Not Approved final decisions from Ready for Release and Released to Client events
- [ ] Landowner notifications expose only their own application information

## 17. User / Role Management

- [ ] Staff can list/create/update authorized users
- [ ] Available roles are Staff, Landowner, and Geodetic
- [ ] Activate/deactivate controls work
- [ ] Landowner account linkage works
- [ ] User management remains Staff-only
- [ ] Significant account actions are audit logged

## 18. Security and protected files

- [ ] Guest cannot open protected administrative files
- [ ] Landowner cannot open Staff-only source scans
- [ ] Geodetic cannot open Staff-only source scans unless explicitly authorized by scope
- [ ] `public/storage` is not required for sensitive file delivery
- [ ] Authenticated pages/files use no-store/no-cache behavior as designed
- [ ] Unauthorized direct URLs return redirect/403/404 as appropriate

## 19. Responsive sweep

Check the major portals on phone, tablet, and desktop widths:

- [ ] Login/recovery
- [ ] Staff Dashboard
- [ ] Clearance Applications list/review
- [ ] Landowner/Parcel/Landholding records
- [ ] Source Records
- [ ] Parcel Map
- [ ] Monitoring Reports
- [ ] Audit Logs
- [ ] User Management
- [ ] Landowner portal
- [ ] Geodetic portal

Expected: no horizontal overflow, hidden essential controls, unusable modal, or broken navigation.

## 20. Major route/page sweep

Run:

```bash
php artisan route:list
```

Manually inspect major pages including:

- [ ] `/login`
- [ ] `/staff/dashboard`
- [ ] `/staff/applications`
- [ ] `/staff/records/landowners`
- [ ] `/staff/records/parcels`
- [ ] `/staff/legacy-records`
- [ ] `/staff/parcel-map`
- [ ] `/staff/reports/monitoring`
- [ ] `/staff/audit-logs`
- [ ] `/staff/users`
- [ ] `/landowner/dashboard`
- [ ] `/landowner/parcels`
- [ ] `/landowner/applications`
- [ ] `/landowner/parcel-map`
- [ ] `/geodetic/dashboard`
- [ ] `/geodetic/parcels`
- [ ] `/geodetic/parcel-map`
- [ ] `/profile`

Expected: no broken routes, obsolete project branding, outdated Approved/Denied user-facing workflow language, or wording that exceeds the approved clearance-processing scope.
