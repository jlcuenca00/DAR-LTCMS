# Tester Data Entry Guide

This guide explains how a tester should fill the barebones system using the system interface.

## 1. Staff login

Log in using:

```text
Email: staff.tester@dar-ltcms.local
Password: password
```

The staff account is the starting operator account.

## 2. Create test user accounts

Go to:

```text
Staff Dashboard → User / Role Management
```

Create only the accounts needed for testing:

- staff account for another encoder/reviewer if needed
- landowner account for landowner portal testing
- geodetic account for read-only parcel/reference review testing

For a landowner user, the account must be linked to a landowner record before the landowner portal can show that landowner's own data.

## 3. Create landowner records

Go to:

```text
Staff Dashboard → Landowner Records
```

Create the test landowner profile using realistic but non-sensitive test data.

Recommended fields to prepare:

- full name
- contact number
- address line
- barangay
- municipality
- province

## 4. Link landowner user account

After creating a landowner record, link the landowner user account through the staff-side user/landowner linkage controls.

Expected result:

- the landowner can log in
- the landowner can only see records linked to their own landowner account
- the landowner cannot see other landowners' data

## 5. Create parcel records

Go to:

```text
Staff Dashboard → Parcel Records
```

Create parcel test data.

Recommended fields to prepare:

- parcel code
- title number, if applicable
- tax declaration number, if applicable
- municipality
- barangay
- province
- area in hectares
- parcel status
- agricultural classification/status
- remarks
- GeoJSON geometry, if map testing is needed

Agricultural classification supports record organization and filtering. It is not an automatic approval gate and does not mutate ownership.

## 6. Create landholding records

Open the landowner details page or parcel details page and add/link landholding data.

A landholding represents the administrative relationship between a landowner and a parcel or land area in the system.

Recommended fields:

- landowner
- parcel
- area in hectares
- status
- source/reference number, if available
- remarks

## 7. Create source records or source packages

Go to:

```text
Staff Dashboard → Source Records
```

Encode source/reference information if the test scenario needs documentary or legacy reference data.

Source records should be treated as documentary/reference origins. They support review and traceability, but they do not automatically prove or execute ownership transfer.

## 8. Encode a clearance application

Go to:

```text
Staff Dashboard → Clearance Applications
```

Create a land transfer clearance application manually as staff.

Recommended flow:

1. Encode application details.
2. Select or link transferor/transferee landowner records if available.
3. Link parcel records if applicable.
4. Save the application.
5. Upload required supporting documents.
6. Encode document metadata/indexing fields.
7. Submit the application for review.

Landowners do not create applications themselves.

## 9. Upload documents and metadata

Upload supporting documents through the staff application review page.

For document metadata/indexing, encode only selected reference fields such as:

- title number
- tax declaration number
- document reference number
- date issued, if available
- issuing office, if available
- remarks

Do not treat metadata capture as automatic legal verification.

## 10. Submit, decide, and verify locking

After the application is complete, submit it for review, then test one final decision path:

- approved
- not approved

After a final decision, verify:

- application editing is locked
- uploads are locked
- backend rejects further changes
- final decision output is viewable by authorized users
- audit logs record the action
- notification appears for the proper user role

## 11. Test role-based access

### Staff

Staff should be able to encode records, process applications, view reports, view audit logs, manage users, and use the parcel map.

### Landowner

Landowner should only see their own parcels, applications, status, decision output, and map records.

### Geodetic

Geodetic should have limited/read-only parcel, source/reference, and map review access. Geodetic users should not approve applications, edit ownership/application records, upload documents, or generate clearances.

## 12. Test notification dropdown

Use the topbar bell.

Expected behavior:

- bell opens recent notification panel
- clicking outside closes the panel
- clicking a notification opens the related page
- See all notifications opens the full notification archive page
- users only see their own notifications

## 13. Test monitoring and reports

After creating enough test data, open Monitoring Reports.

Confirm:

- counts update correctly
- status breakdowns reflect test records
- final decision records appear correctly
- report output remains administrative/monitoring focused
