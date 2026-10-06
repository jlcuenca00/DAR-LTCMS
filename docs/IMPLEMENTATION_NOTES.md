# DAR-LTCMS Multi-party Implementation Notes

These are feature-specific notes, not the complete release or installation procedure. Use the [README](../README.md), [system baseline](FINAL_SYSTEM_BASELINE.md) and [release guide](RELEASE_PREPARATION.md) for the current project.

## Implemented

- Multiple transferors and transferees are now linked as separate Landowner Records on the application review page.
- Auto-create acts on one selected party row, preventing combined names from becoming one Landowner Record.
- Every party must be separately linked before release.
- Staff may record per-parcel hectare shares and optionally split them equally.
- The explicit **Update current transferor landholdings** action creates or updates separate active Landholding rows for positive co-owner shares.
- Combined active shares are prevented from exceeding the Parcel Record area.
- Multiple transferees are evaluated separately by the assistive five-hectare validation.
- Secondary linked landowners can view only applications connected to their own Landowner Record and receive applicable notifications.
- Staff Landowner Details now shows applications linked through the multi-party JSON records.
- Landowner clearance output reuses the same official LTC Form No. 5 print view used by Staff.
- Reusable role-aware breadcrumbs were added, and ordinary internal Back buttons were removed.

## Scope safeguard

Application approval or release does not create a transferee landholding, transfer ownership, or mutate registry records. Current transferor landholding shares are updated only when authorized Staff explicitly select the synchronization option during application review.

## Database

This feature's original patch added no migration. The current `transferors` and `transferees` JSONB fields and the existing unique `(landowner_id, parcel_id)` landholding structure are used.

## Local/staging verification commands

Use an isolated test database. These are not production deployment instructions.

```bash
composer install
npm ci
php artisan migrate
php artisan test --filter=MultipleApplicationPartyLinkTest
php artisan test
npm run build
```

The added feature tests cover separate party creation, co-owner share synchronization, per-transferee hectare validation, secondary-landowner privacy, staff related-application visibility, and finalized-record locking.
