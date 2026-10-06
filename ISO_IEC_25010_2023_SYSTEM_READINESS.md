# DAR-LTCMS ISO/IEC 25010:2023 System Readiness

This document maps implemented DAR-LTCMS controls to the nine product-quality characteristics in [ISO/IEC 25010:2023](https://www.iso.org/standard/78176.html). It is an engineering readiness assessment, not ISO certification, a measured evaluator score, or a guarantee of legal/administrative outcomes.

## 1. Functional Suitability

**Implemented controls:** Staff-encoded applications, role-specific portals, Landowner/Parcel/Landholding records, supporting-document review, multi-party linking, DAR office workflow tracking, an Approved-only current final decision with Compliance Required for blocking issues, separate release tracking, LTC Form No. 5 output, Monitoring Reports, notifications, and Audit Logs.

**Scope boundary:** these functions support clearance processing and records management only. An Approved clearance or a recorded client release does not automatically transfer land ownership or alter registry records.

## 2. Performance Efficiency

**Implemented controls:** pagination, targeted eager loading, indexed operational tables, database-backed application stores, and performance regression coverage.

**Hardening applied:** major dashboard/report/listing queries, Parcel Map data, search/filter paths, and common operational indexes were reviewed during the performance pass. Development/testing also detects avoidable lazy-loading behavior.

## 3. Compatibility

DAR-LTCMS uses standard HTTP/HTTPS, Laravel/Blade, PostgreSQL, modern browsers, GeoJSON, Leaflet, and external basemap resources. Network-dependent map resources remain part of deployment/browser validation.

Responsive regression coverage checks phone, tablet, and desktop viewport behavior. Final real-device/browser verification remains appropriate for formal evaluation.

## 4. Interaction Capability

Role-specific dashboards, breadcrumbs, validation messages, confirmation dialogs, final-state notices, requirement-specific fields, print views, responsive layouts, and protected navigation support learnability and operability.

Final usability evaluation should use Staff, Landowner, and Geodetic scenarios that match each role's actual permissions.

## 5. Reliability

Database transactions protect important multi-record actions. Approved applications are locked against further core editing and supporting-document mutation, including model-level final-record protection. Historical Not Approved / Denied records remain read-only compatibility records. Clearance generation preserves final output data. The `/up` health endpoint is available.

Production release preparation now includes database/private-file backups, exact deployed-commit recording, a read-only release check, smoke testing, and controlled rollback guidance.

## 6. Security

**Implemented controls include:**

- authenticated role-based access control
- login throttling and secure session behavior
- CSRF protection and session regeneration
- active-account enforcement
- Staff-managed account administration
- Landowner record isolation
- limited Geodetic access
- protected administrative file delivery
- rejection of unsupported/executable uploads where applicable
- final-decision locking
- actor/timestamp/context Audit Logs
- security headers and authenticated no-store/no-cache behavior
- trusted-host/proxy handling
- production configuration readiness checks
- production PHP/JavaScript dependency security audits

Sensitive administrative uploads are not intentionally exposed through a public `storage` symlink.

## 7. Maintainability

Laravel MVC separation, service classes, migrations, reusable Blade components, configuration files, regression tests, and documented release procedures support modification, diagnosis, and controlled deployment.

The final repository also includes a canonical system baseline and documentation-alignment rules to reduce scope/terminology drift.

## 8. Flexibility

Environment-based configuration supports local development, controlled testing, and the CloudPanel production deployment. `.env.example` documents PostgreSQL, sessions, storage, HTTPS, mail, trusted hosts, and trusted proxies without exposing production secrets.

The production site is deployed at `https://darltcms.me`; final `v1.0.0` release status still depends on completion of the live release gate described in `docs/RELEASE_PREPARATION.md`.

## 9. Safety

The project discussion below describes safeguards for administrative records. It does not establish that each listed safeguard satisfies the standard's Safety subcharacteristics; applicability and evaluation evidence must be assessed explicitly:

- strict role restrictions
- Landowner privacy isolation
- limited Geodetic access
- validation warnings/checks
- final-decision locking
- protected files
- data-integrity checks
- audit trails
- preserved final outputs

Obsolete automatic ownership/registry mutation artifacts were removed. **Approved/GRANTED clearance and subsequent release tracking only record the administrative clearance result and its delivery; they do not execute legal ownership transfer or registry alteration.**

## Stage 1 verification and final-release work

Stage 1 closed on 7 October 2026 with a clean live record/configuration check, actual isolated off-site backup/database/file recovery, confirmed email receipt, a configured backup-failure alert and exact deployed-version verification. See [release preparation](docs/RELEASE_PREPARATION.md) for dated evidence. These checks used the current mock dataset and do not establish formal evaluator ratings.

Before formal evaluation/final release:

1. Restore strict SSH host verification only after Jake explicitly says his defense is finished; this remains required for the planned final release.
2. Repeat the live production `php artisan dar:release-check` with no blockers/warnings.
3. Create and verify the final production database and private-file backups.
4. Confirm `.release-commit` matches the intended `main` commit after deployment.
5. Complete the production smoke test, including role isolation and LTC Form No. 5 output.
6. Conduct any required evaluator-led UAT/usability sessions using the final scenarios.
7. Capture final thesis screenshots from the validated baseline.
8. Create the `v1.0.0` tag only after the production release gate is actually complete.
