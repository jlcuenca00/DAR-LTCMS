<?php

$completed = false;
// Laravel renders uncaught exceptions in these standalone bootstrapped scripts.
// Require explicit completion so every guard/assertion failure also fails CI.
register_shutdown_function(function () use (&$completed): void {
    if (! $completed) {
        fwrite(STDERR, "Browser fixture/verification script did not complete.\n");
        exit(1);
    }
});

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$environmentFile = getenv('GITHUB_ENV');
if (getenv('CI') !== 'true' || ! $app->environment('testing')
    || ! $environmentFile || ! is_writable($environmentFile)
    || config('database.default') !== 'pgsql'
    || ! in_array(Illuminate\Support\Facades\DB::connection()->getConfig('host'), ['127.0.0.1', 'localhost'], true)) {
    throw new RuntimeException('Application review fixtures require isolated CI testing PostgreSQL.');
}

$staff = App\Models\User::query()
    ->where('username', getenv('E2E_STAFF_USERNAME'))
    ->where('role', App\Models\User::ROLE_STAFF)
    ->where('is_active', true)
    ->firstOrFail();

$applicationId = Illuminate\Support\Facades\DB::transaction(function () use ($staff): int {
    $parcel = App\Models\Parcel::query()->firstOrCreate(
        ['parcel_code' => 'E2E-REVIEW-PARCEL'],
        [
            'title_no' => 'T-E2E-REVIEW', 'tax_decl_no' => 'TD-E2E-REVIEW',
            'lot_number' => 'LOT-E2E-REVIEW', 'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan', 'province' => 'Negros Oriental',
            'area_hectares' => 1.25, 'area_square_meters' => 12500, 'status' => 'active',
        ]
    );

    $parties = [];
    foreach (['transferor', 'transferee'] as $side) {
        foreach ([1, 2] as $index) {
            $owner = App\Models\Landowner::query()->firstOrCreate(
                ['first_name' => 'E2E Review', 'last_name' => ucfirst($side).' '.$index],
                ['province' => 'Negros Oriental']
            );
            $parties[$side][] = [
                'name' => $owner->full_name, 'landowner_id' => $owner->id,
                'parcel_shares' => [],
            ];
        }

        App\Models\RequiredDocument::query()->firstOrCreate(
            ['name' => 'E2E Review '.ucfirst($side).' Requirement', 'applies_to' => $side],
            ['is_mandatory' => true, 'blocks_acceptance' => true]
        );
    }

    $application = App\Models\LandTransferApplication::query()->firstOrCreate(
        ['application_code' => 'E2E-REVIEW-001'],
        [
            'transferor_name' => $parties['transferor'][0]['name'],
            'transferee_name' => $parties['transferee'][0]['name'],
            'transferors' => $parties['transferor'], 'transferees' => $parties['transferee'],
            'transferor_landowner_id' => $parties['transferor'][0]['landowner_id'],
            'transferee_landowner_id' => $parties['transferee'][0]['landowner_id'],
            'municipality' => 'Dumaguete City', 'barangay' => 'Bantayan',
            'status' => App\Models\LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]
    );

    if ($application->status !== App\Models\LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW
        || (int) $application->encoded_by !== (int) $staff->id) {
        throw new RuntimeException('Application review fixture is not in the expected initial state.');
    }

    $applicationParcel = App\Models\ApplicationParcel::query()->firstOrCreate(
        ['land_transfer_application_id' => $application->id, 'parcel_id' => $parcel->id],
        [
            'parcel_code' => $parcel->parcel_code, 'title_no' => $parcel->title_no,
            'tax_decl_no' => $parcel->tax_decl_no, 'lot_number' => $parcel->lot_number,
            'area_hectares' => 1.25, 'area_square_meters' => 12500,
        ]
    );

    foreach (['transferor', 'transferee'] as $side) {
        foreach ($parties[$side] as &$party) {
            $party['parcel_shares'] = [(string) $applicationParcel->id => 0.625];
        }
        unset($party);
    }
    $application->forceFill(['transferors' => $parties['transferor'], 'transferees' => $parties['transferee']])->save();
    app(App\Services\ApplicationPartyShareIntegrityService::class)->assertValid($application);

    return $application->id;
});

if (file_put_contents($environmentFile, "E2E_REVIEW_APPLICATION_ID={$applicationId}\n", FILE_APPEND) === false) {
    throw new RuntimeException('Could not export the application review fixture ID.');
}
echo "Application review fixture prepared: E2E-REVIEW-001 (ID {$applicationId}).\n";
$completed = true;
