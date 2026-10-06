# Historical ISO Hardening Patch Notes

This file describes an earlier patch package. Those changes are already part of the repository; do not copy the old patch files over the current system or rerun its old apply commands.

## Current procedures

- [Local development setup](README.md#local-development-setup)
- [Local/staging tester reset](docs/barebones-tester-handoff.md)
- [Production backup, deployment and recovery](docs/RELEASE_PREPARATION.md)
- [Current engineering readiness assessment](ISO_IEC_25010_2023_SYSTEM_READINESS.md)

The earlier instruction that `/register` is unavailable is obsolete. The current guest routes support Landowner registration, including the configured Google registration flow; account access follows Staff review/linking. Staff and Geodetic accounts are managed through authorized Staff controls.

Username remains the login identifier. Password recovery uses the implemented email-confirmed code flow where available, with Staff-assisted reset for other cases.

Approved is the only current final decision. Historical negative records remain read-only. Release is tracked separately and does not change land ownership.

Do not run `optimize:clear` as a routine production update step: the current deployment preserves runtime cache, including authentication throttles. Do not run local test/reset commands on production.

## Historical migration note

The earlier patch removed obsolete automatic ownership/registry-mutation structures because those operations are outside DAR-LTCMS scope. This historical note is not permission to reverse or manually reapply that migration.
