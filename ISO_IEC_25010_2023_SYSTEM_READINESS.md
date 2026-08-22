# DAR-LTCMS ISO/IEC 25010:2023 System Readiness

This document maps implemented DAR-LTCMS controls to the nine product-quality characteristics used by the project evaluation. It is an engineering readiness assessment, not a guarantee of evaluator ratings or legal/administrative outcomes.

## 1. Functional Suitability

**Implemented controls:** Staff-encoded applications, role-specific portals, Landowner/Parcel/Landholding records, supporting-document review, multi-party linking, DAR office workflow tracking, Released/Denied final outcomes, LTC Form No. 5 output, Monitoring Reports, notifications, and Audit Logs.

**Scope boundary:** these functions support clearance processing and records management only. A Released clearance does not automatically transfer land ownership or alter registry records.

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

Database transactions protect important multi-record actions. Released/Denied applications are locked against further editing and supporting-document mutation. Clearance generation preserves final output data. The `/up` health endpoint is available.

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

DAR-LTCMS is not safety-critical in the industrial/medical ISO sense, but it contains important operational safeguards for sensitive administrative records:

- strict role restrictions
- Landowner privacy isolation
- limited Geodetic access
- validation warnings/checks
- final-decision locking
- protected files
- data-integrity checks
- audit trails
- preserved final outputs

Obsolete automatic ownership/registry mutation artifacts were removed. **Released/GRANTED clearance only records and generates the administrative clearance result; it does not execute legal ownership transfer or registry alteration.**

## Residual items before final formal evaluation / v1.0.0

1. Complete the live production `php artisan dar:release-check` with no blockers/warnings.
2. Create and verify the final production database and private-file backups.
3. Confirm `.release-commit` matches the intended `main` commit after deployment.
4. Complete the production smoke test, including role isolation and LTC Form No. 5 output.
5. Conduct any required evaluator-led UAT/usability sessions using the final scenarios.
6. Capture final thesis screenshots from the validated baseline.
7. Create the `v1.0.0` tag only after the production release gate is actually complete.
