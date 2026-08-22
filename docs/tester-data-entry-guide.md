# Tester Data Entry Guide

This guide explains how a tester should populate the DAR-LTCMS barebones test environment through the web interface.

> Use realistic but non-sensitive test data. The barebones reset and seeded credentials are for local/staging testing only.

## 1. Staff login

Use the primary tester account:

```text
Email: staff.tester@dar-ltcms.local
Password: password
```

The barebones seeder also creates four additional Staff tester accounts for multi-actor testing.

## 2. Create test user accounts

Go to:

```text
Staff Dashboard → User / Role Management
```

Create only the accounts required by the test scenario:

- Landowner account for Landowner portal/privacy testing
- Geodetic account for limited read-only parcel/reference/map testing
- additional Staff accounts only when a multi-actor workflow is required

A Landowner user must be linked to the correct Landowner record before that user can see their own records.

## 3. Create Landowner records

Go to:

```text
Staff Dashboard → Landowner Records
```

The current Landowner record fields are:

- First name
- Middle name
- Last name
- Suffix
- Contact number
- Linked Landowner user account / no linked account
- Address
- Municipality
- Barangay
- Province

Do not invent unavailable profile fields such as sex, civil status, or birthdate.

## 4. Link the Landowner account

Link the Landowner user account to the correct Landowner record through the Staff controls.

Expected result:

- the Landowner can log in;
- the Landowner sees only records tied to that linked Landowner record; and
- direct access to another Landowner's records is rejected.

## 5. Create Parcel records

Go to:

```text
Staff Dashboard → Parcel Records
```

Prepare the parcel information required by the current form, such as:

- parcel code
- title/tax declaration references when applicable
- municipality
- barangay
- province
- parcel area
- agricultural classification/status
- map geometry/reference information when map testing is needed

Agricultural classification supports administrative organization, review, filtering, and monitoring. It is not an automatic legal approval gate and does not transfer ownership.

## 6. Create Landholding records

Open the relevant Landowner or Parcel record and create/link the Landholding relationship.

A Landholding represents the administrative relationship between a Landowner and Parcel/area in DAR-LTCMS. It is a records-management relationship; changing or approving a clearance does not automatically mutate legal ownership.

## 7. Create Source Records / Source Record Packages

Go to:

```text
Staff Dashboard → Source Records
```

Encode source/reference information when the scenario needs documentary, historical, or reference data. Preserve provenance and use the source scope that matches the record.

Source records support review and traceability. They are not automatic proof of legal ownership transfer.

## 8. Encode a Clearance Application

Go to:

```text
Staff Dashboard → Clearance Applications
```

Staff manually encode applications. Landowners do not create applications themselves.

Recommended flow:

1. Encode the application details.
2. Encode/select the transferor/s and transferee/s.
3. Link the relevant Parcel record/s.
4. Select the transfer nature/instrument information.
5. Save the application.
6. Upload or review supporting requirements.
7. Fill the requirement-specific data fields.
8. Submit/process the application through the authorized office workflow.

## 9. Requirement/document data fields

The visible fields depend on the requirement type. Use the labels actually shown by the system.

Common fields include:

- Title number, Tax Declaration number, Official Receipt number, Certificate number, or other Document number as applicable
- Date issued
- Lot / parcel shown in the document when applicable
- Names appearing in the document when applicable
- Transfer document title/type, transfer area, transferor/s, and transferee/s for transfer instruments
- Notarizer / lawyer name and Date notarized for notarized documents
- Notarial Document No., Page No., Book No., and Series when applicable
- MARPO Certification / LTC Form No. 2 review checks when applicable
- Verification notes

The old generic **Reference Number** and **Issuing Office** instructions are no longer part of this guide because the current form uses requirement-specific fields instead.

File upload is supporting evidence; metadata capture and validation checks remain assistive and do not replace DAR legal/administrative determination.

## 10. Process the application workflow

For a complete positive-path test, process an application through:

1. Pending Review by Legal Officer
2. Endorsed to LTI Division
3. Endorsed to Chief Legal
4. Endorsed to PARPO II
5. For Releasing
6. Released

Also test a separate application that ends in **Denied**.

Released and Denied are final user-facing states. After either final state, verify that:

- application editing is locked;
- supporting-document upload/removal is locked;
- backend mutation attempts are rejected;
- final output remains viewable by authorized users;
- audit logs preserve the decision/action history; and
- role-appropriate notifications are generated.

## 11. Verify LTC Form No. 5

For final applications, verify the official output:

- LTC number uses the annual sequence and page value;
- result is GRANTED for Released or DENIED for Denied;
- all linked parcel references/areas are represented correctly;
- signatory is `ENGR. MANUEL M. GALON, JR., OIC PARPO II`;
- notarial details display when encoded;
- print/PDF uses the intended 8.5 x 13 inch format; and
- the output does not claim that ownership or registry records were automatically changed.

## 12. Test role-based access

### Staff

Staff may encode/manage records, process applications, review/upload documents, generate outputs/reports, view audit logs, manage authorized accounts, and use the Staff Parcel Map.

### Landowner

Landowners may view only their own linked parcel/application/status/final-output information. They must never see another Landowner's records and do not create applications.

### Geodetic Personnel

Geodetic users have limited/read-only parcel, source/reference, and map access. They are not clearance approving/processing users and must not broadly edit ownership/application records.

## 13. Test notifications

Use the notification bell/archive and verify that:

- users see only notifications intended for them;
- important application events link to the correct authorized page; and
- final Released/Denied events display using current terminology.

## 14. Test monitoring and reports

After creating enough test data, open Monitoring Reports and confirm:

- counts and filters reflect the test records;
- current workflow statuses are used;
- Released and Denied output totals are correct;
- Recorded Output Area is presented as an administrative/reporting metric; and
- report wording does not imply automatic legal land transfer.
