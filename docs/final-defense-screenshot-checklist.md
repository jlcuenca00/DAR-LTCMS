# Final Defense Screenshot Checklist

Capture screenshots from one recorded, validated DAR-LTCMS version using realistic non-sensitive test/demo data. Record the date, user role and deployed version. If UI changes affect a thesis figure, recapture that figure and update its caption.

## Core system identity

- [ ] Login page with DAR-LTCMS branding
- [ ] Staff Dashboard
- [ ] Landowner Dashboard
- [ ] Geodetic Dashboard

## Staff workflow screenshots

- [ ] Clearance Applications list with current status labels
- [ ] Application create/encode form
- [ ] Application review page
- [ ] Supporting document upload/review section
- [ ] Requirement-specific document data fields
- [ ] Workflow stage controls
- [ ] Request Compliance / Action Required Staff workflow
- [ ] Landowner persistent Action Required alert
- [ ] Compliance Resolved / resumed workflow state
- [ ] Approved final decision
- [ ] Ready for Release / Released to Client tracking
- [ ] Locked finalized application state
- [ ] Browser LTC Form No. 5 output
- [ ] PDF LTC Form No. 5 output

## LTC Form No. 5 proof screenshots

Capture at least one clear final output showing:

- [ ] LTC number and page value
- [ ] GRANTED result for the current Approved workflow
- [ ] linked Parcel references/area
- [ ] recorded PARPO II decision officer/signatory matches the final-decision metadata
- [ ] notarial details when populated
- [ ] 8.5 x 13 inch print/PDF layout
- [ ] no wording that suggests automatic ownership/registry mutation

## Records management screenshots

- [ ] Landowner Records list/search
- [ ] Landowner details/current fields
- [ ] Parcel Records list/search
- [ ] Parcel details
- [ ] Parcel edit/current agricultural classification control
- [ ] Landholding section/details

## Source/reference screenshots

- [ ] Source Records index
- [ ] Source Record Package encode page
- [ ] Source Record Package details/provenance
- [ ] Protected reference file view
- [ ] Import template/preview when included in the defense

## Map screenshots

- [ ] Staff Parcel Map with multiple test Parcels
- [ ] Staff map filter/hover/click behavior
- [ ] Geodetic limited-access Parcel Map / controlled geometry workflow
- [ ] Landowner privacy-filtered Parcel Map

## Monitoring and audit screenshots

- [ ] Monitoring Reports dashboard with current statuses
- [ ] Printable Monitoring Report
- [ ] Recorded Output Area label/scope notice
- [ ] Audit Logs — Activity (record/workflow actions)
- [ ] Login History (authentication events separately)
- [ ] Expanded audit event showing actor/timestamp/context

## Administration screenshots

- [ ] User / Role Management list
- [ ] User create/edit form
- [ ] Profile settings page

## Landowner privacy proof

- [ ] Landowner A own Parcel/Application data
- [ ] Landowner B own Parcel/Application data
- [ ] 403/404/denied result when attempting another Landowner's protected record

## Geodetic restriction proof

- [ ] Geodetic Parcel list/details
- [ ] Geodetic map/reference review
- [ ] Authorized geometry-only editor, including stale/invalid-save feedback
- [ ] 403/denied result when attempting a Staff-only application/action

## Suggested screenshot naming

```text
01-login-page.png
02-staff-dashboard.png
03-clearance-applications.png
04-application-review.png
05-requirement-data-fields.png
06-compliance-required-staff.png
07-landowner-action-required.png
08-compliance-resolved.png
09-approved-locked-state.png
10-release-tracking.png
11-ltc-form5-output.png
12-landowner-records.png
13-parcel-records.png
14-source-records.png
15-staff-parcel-map.png
16-landowner-map-privacy.png
17-geodetic-map-review.png
18-geodetic-geometry-editor.png
19-monitoring-report.png
20-audit-activity.png
21-login-history.png
22-user-management.png
```

## Screenshot quality rules

- Use DAR-LTCMS branding only; do not show obsolete project branding.
- Use **Approved** as the only current final-decision terminology. Show Compliance Required as an open corrective state and release as a separate delivery status. Historical Not Approved / Denied records, if shown, must be clearly labeled historical.
- Do not expose real production credentials, personal data, `.env` values, or private file paths.
- Avoid browser clutter that distracts from the system.
- Capture enough of reports/outputs to make labels and scope understandable.
- Keep clearance-only limitation wording visible when it materially supports the thesis explanation.
