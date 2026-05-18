# Final Manual Testing Checklist

Use this checklist before defense and before the final database/demo export.

## 1. Setup and baseline

- [ ] Run `composer install`
- [ ] Run `npm install`
- [ ] Confirm `.env` uses PostgreSQL database `dar_iland`
- [ ] Run `php artisan migrate:fresh --seed`
- [ ] Run `php artisan db:seed --class=LandownerPrivacyDemoSeeder`
- [ ] Run `php artisan db:seed --class=ParcelMapDemoSeeder`
- [ ] Run `npm run build`
- [ ] Run `php artisan test`

Expected result: all tests pass.

## 2. Login and role redirects

- [ ] Staff can log in
- [ ] Landowner A can log in
- [ ] Landowner B can log in
- [ ] Geodetic user can log in if created through User Management
- [ ] Inactive account cannot continue using the system
- [ ] Each role lands on its own dashboard

Expected result: role-based dashboards and navigation are correct.

## 3. Staff dashboard and navigation

- [ ] Staff Dashboard loads
- [ ] Quick action cards point to correct modules
- [ ] Sidebar active states are correct
- [ ] User Management appears in the administration/footer area, not as a main workflow item
- [ ] Logout works

Expected result: staff navigation is polished, consistent, and not cluttered.

## 4. Staff application processing

- [ ] Staff can open Clearance Applications
- [ ] Staff can create/encode an application
- [ ] Staff can link or create landowner records from application parties
- [ ] Staff can upload supporting documents
- [ ] Staff can encode document metadata/indexing fields
- [ ] Staff can submit an application for review
- [ ] Staff can approve an application
- [ ] Staff can mark an application as not approved
- [ ] Browser decision output opens
- [ ] PDF decision output downloads/opens

Expected result: approval generates/records clearance result only. It must not imply automatic land ownership transfer or registry mutation.

## 5. Final decision lock

Test one approved and one not-approved application.

- [ ] Edit controls are hidden/disabled after final decision
- [ ] Document upload is locked after final decision
- [ ] Backend rejects post-final update attempts
- [ ] Backend rejects post-final upload/removal attempts
- [ ] UI clearly shows final/locked state
- [ ] Decision output remains viewable by authorized users

Expected result: final decision records are preserved and protected.

## 6. Landowner privacy

Using `LandownerPrivacyDemoSeeder`:

- [ ] Login as `landowner.a@test.com`
- [ ] Confirm Alpha parcel/application appears
- [ ] Confirm Bravo parcel/application does not appear
- [ ] Attempt to open Bravo parcel/application URL directly
- [ ] Confirm direct access is denied
- [ ] Login as `landowner.b@test.com`
- [ ] Confirm Bravo parcel/application appears
- [ ] Confirm Alpha parcel/application does not appear
- [ ] Attempt to open Alpha parcel/application URL directly
- [ ] Confirm direct access is denied

Expected result: landowners only see their own linked records.

## 7. Geodetic read-only access

- [ ] Geodetic dashboard loads
- [ ] Geodetic can open parcel records
- [ ] Geodetic can open parcel detail pages
- [ ] Geodetic can open parcel map viewer
- [ ] Geodetic cannot open staff clearance applications
- [ ] Geodetic cannot approve/not approve applications
- [ ] Geodetic cannot upload supporting documents
- [ ] Geodetic cannot access User Management
- [ ] Geodetic cannot generate clearance decisions

Expected result: geodetic access remains limited/read-only.

## 8. Landowner portal

- [ ] Landowner dashboard loads
- [ ] Own parcel records list loads
- [ ] Own application status list loads
- [ ] Finalized decision output is viewable only for own linked applications
- [ ] Decision PDF is viewable/downloadable only for own linked applications
- [ ] Parcel map is privacy-filtered
- [ ] Profile page matches role styling

Expected result: landowner portal is useful but privacy-restricted.

## 9. Parcel records and agricultural classification

- [ ] Staff Parcel Records page loads
- [ ] Staff can search/filter parcel records
- [ ] Agricultural classification filter is a dropdown
- [ ] Parcel edit form uses agricultural classification dropdown
- [ ] Labels do not say “crop land use” where agricultural classification is intended
- [ ] Staff/geodetic/landowner visibility rules are still correct
- [ ] `non_agricultural` is treated as reference/legacy/exception data only

Expected result: agricultural classification supports records management only and does not become an approval gate.

## 10. Source records / legacy records

- [ ] Source Records page loads
- [ ] Encode Source Record Package works
- [ ] Import template downloads
- [ ] Import preview page works
- [ ] Valid rows and errors are visually understandable
- [ ] Source record details page loads
- [ ] Link parcel/create parcel actions work for staff
- [ ] Link landowner/create landowner actions work for staff
- [ ] Crop/land-use wording has been replaced with agricultural classification wording/dropdowns

Expected result: source records support review, provenance, and encoding/import workflows.

## 11. Parcel map viewer

- [ ] Staff map loads broad parcel data
- [ ] Staff map hover/click behavior works
- [ ] Staff map opens parcel details
- [ ] Geodetic map loads read-only parcel/reference data
- [ ] Landowner map loads only own linked parcel data
- [ ] Basemap is clean and does not show heavy distracting boundary/water lines
- [ ] Demo irregular parcels display properly

Expected result: map viewer supports review and monitoring only. It does not mutate parcel ownership.

## 12. Monitoring reports

- [ ] Monitoring dashboard loads
- [ ] Summary counts display
- [ ] Status/decision breakdowns display
- [ ] Municipality breakdown displays
- [ ] Recent applications/clearances display
- [ ] Printable report opens
- [ ] Print / Save as PDF is visible
- [ ] Scope notice appears
- [ ] Signature areas appear

Expected result: report is defense-ready and print-ready.

## 13. Audit logs

- [ ] Audit Log Viewer loads
- [ ] Filter by action works
- [ ] Filter by application code works
- [ ] Filter by actor works
- [ ] Expandable metadata/details are readable
- [ ] Application links return to review page
- [ ] Important actions are logged: document upload/removal, submit, approve, not-approved, clearance generation, user create/update

Expected result: important system actions remain traceable.

## 14. User / Role Management

- [ ] Staff can list users
- [ ] Staff can create users
- [ ] Staff can assign role: staff, landowner, geodetic
- [ ] Staff can activate/deactivate users
- [ ] Landowner account linking works
- [ ] One-user-to-one-landowner rule is protected
- [ ] Staff cannot accidentally self-demote/self-deactivate if blocked by implementation
- [ ] User create/update actions are audit logged

Expected result: user administration is staff-only and traceable.

## 15. Final route/page sweep

Run:

```bash
php artisan route:list
```

Manually inspect major pages:

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

Expected result: no broken routes, no old/unpolished leftover page, no scope-breaking wording.
