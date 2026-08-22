# Final Defense Screenshot Checklist

Capture screenshots only from the final polished DAR-LTCMS UI using realistic non-sensitive test/demo data.

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
- [ ] Released final state
- [ ] Denied final state
- [ ] Locked finalized application state
- [ ] Browser LTC Form No. 5 output
- [ ] PDF LTC Form No. 5 output

## LTC Form No. 5 proof screenshots

Capture at least one clear final output showing:

- [ ] LTC number and page value
- [ ] GRANTED or DENIED result
- [ ] linked Parcel references/area
- [ ] `ENGR. MANUEL M. GALON, JR., OIC PARPO II`
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
- [ ] Geodetic read-only Parcel Map
- [ ] Landowner privacy-filtered Parcel Map

## Monitoring and audit screenshots

- [ ] Monitoring Reports dashboard with current statuses
- [ ] Printable Monitoring Report
- [ ] Recorded Output Area label/scope notice
- [ ] Audit Log Viewer
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
- [ ] Geodetic map read-only state
- [ ] 403/denied result when attempting a Staff-only application/action

## Suggested screenshot naming

```text
01-login-page.png
02-staff-dashboard.png
03-clearance-applications.png
04-application-review.png
05-requirement-data-fields.png
06-released-locked-state.png
07-denied-locked-state.png
08-ltc-form5-output.png
09-landowner-records.png
10-parcel-records.png
11-source-records.png
12-staff-parcel-map.png
13-landowner-map-privacy.png
14-geodetic-map-readonly.png
15-monitoring-report.png
16-audit-log-viewer.png
17-user-management.png
```

## Screenshot quality rules

- Use DAR-LTCMS branding only; do not show obsolete DAR-iLAND labels.
- Use the current **Released/Denied** user-facing workflow terminology.
- Do not expose real production credentials, personal data, `.env` values, or private file paths.
- Avoid browser clutter that distracts from the system.
- Capture enough of reports/outputs to make labels and scope understandable.
- Keep clearance-only limitation wording visible when it materially supports the thesis explanation.
