# DAR-LTCMS ISO/IEC 25010:2023 System Readiness

This document maps the implemented controls of DAR-LTCMS to the nine product-quality characteristics used by the project questionnaire. It is an engineering readiness assessment, not a guarantee of evaluator ratings.

## 1. Functional Suitability

**Strong controls:** staff-encoded applications, role-specific portals, parcel/landholding records, supporting-document review, multi-party linking, final decisions, LTC Form No. 5 output, monitoring reports, notifications, and audit logs.

**Hardening applied:** public registration was removed so accounts follow the approved staff-managed workflow. Username-based account confirmation and optional email handling are now consistent.

## 2. Performance Efficiency

**Strong controls:** pagination, eager loading in major listings, database-backed cache, and indexed operational tables.

**Hardening applied:** additional indexes were added for application queues, location filtering, audit history, and notifications. Development now detects lazy-loading problems.

## 3. Compatibility

The system uses standard HTTP/HTTPS, Laravel/Blade, PostgreSQL, modern browsers, GeoJSON, Leaflet, OpenStreetMap, and CARTO. External map resources remain network-dependent and should be included in deployment testing.

## 4. Interaction Capability

The role-specific dashboards, breadcrumbs, validation messages, confirmation dialogs, locked-state notices, print views, and responsive pages support learnability and operability. Continued usability testing with DAR Staff, landowners, and geodetic personnel remains required.

## 5. Reliability

Database transactions protect important multi-record actions. Final decisions lock editing and uploads. Clearance generation uses row locking and update-or-create behavior. The `/up` health endpoint is available. Production backup and restore procedures remain deployment responsibilities and must be tested before turnover.

## 6. Security

**Implemented controls:** username authentication, login throttling, CSRF protection, session regeneration, active-account enforcement, forced password change, staff-assisted resets, role middleware, landowner ownership checks, private document storage, and audit logging.

**Hardening applied:** public registration and self-deletion were removed, password defaults were strengthened, reset actions were throttled, executable uploads were rejected, authenticated HTML was marked no-store, and common security headers were added.

## 7. Maintainability

Laravel MVC separation, service classes, migrations, reusable Blade components, and feature tests support modification and diagnosis. The patch adds targeted regression tests and a safe environment template.

## 8. Flexibility

Environment-based configuration supports local testing and future deployment. `.env.example` now documents PostgreSQL, sessions, queues, storage, and production HTTPS settings. Final hosting remains to be configured and tested.

## 9. Safety

DAR-LTCMS is not safety-critical in the ISO sense. Its important operational safeguards are final-decision locking, validation warnings, role restrictions, and audit trails. Obsolete automatic ownership/registry mutation schema was removed. Approval or release only records and generates a clearance result; it does not execute legal ownership transfer or registry alteration.

## Residual items before formal evaluation

1. Run the complete automated test suite against a clean PostgreSQL testing database.
2. Conduct browser checks in current Chrome, Edge, Firefox, and Safari.
3. Perform concurrent-user response-time testing using the intended deployment server.
4. Test backup restoration, file recovery, and interruption recovery.
5. Confirm HTTPS, secure cookies, production logging, and database access restrictions.
6. Complete role-based user acceptance testing using the exact evaluation scenarios.
