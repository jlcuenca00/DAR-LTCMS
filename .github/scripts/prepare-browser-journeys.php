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
    throw new RuntimeException('Browser journeys require isolated CI testing PostgreSQL.');
}
$staff = App\Models\User::query()->where('username', getenv('E2E_STAFF_USERNAME'))
    ->where('role', 'staff')->where('is_active', true)->firstOrFail();
$password = getenv('E2E_STAFF_PASSWORD');
if (! $password) {
    throw new RuntimeException('Browser journey credentials are missing.');
}

$prepare = function (string $suffix) use ($staff, $password): array {
    return Illuminate\Support\Facades\DB::transaction(function () use ($staff, $password, $suffix): array {
        $ownerUser = App\Models\User::create([
            'username' => $staff->username.'.owner'.strtolower($suffix), 'name' => 'Journey Landowner',
            'email' => $staff->username.'.owner'.strtolower($suffix).'@example.test', 'password' => $password,
            'role' => 'landowner', 'registration_status' => 'approved',
            'is_active' => true, 'must_change_password' => false,
        ]);
        if ($suffix === '') {
            App\Models\User::create([
                'username' => $staff->username.'.geo2', 'name' => 'Journey Second Geodetic',
                'email' => $staff->username.'.geo2@example.test', 'password' => $password,
                'role' => 'geodetic', 'is_active' => true, 'must_change_password' => false,
            ]);
        }
        $transferee = App\Models\Landowner::create([
            'first_name' => 'Journey', 'last_name' => 'Transferee',
            'province' => 'Negros Oriental', 'user_id' => $ownerUser->id,
        ]);
        $applications = [];
        $parcelIds = [];
        foreach (['COMPLIANCE', 'APPROVAL'] as $kind) {
            $transferor = App\Models\Landowner::create([
                'first_name' => 'Journey', 'last_name' => $kind.' Transferor',
                'province' => 'Negros Oriental',
            ]);
            $parcel = App\Models\Parcel::create([
                'parcel_code' => 'E2E-JOURNEY-'.$kind.$suffix, 'title_no' => 'T-JOURNEY-'.$kind.$suffix,
                'tax_decl_no' => 'TD-JOURNEY-'.$kind.$suffix, 'lot_number' => 'LOT-JOURNEY-'.$kind.$suffix,
                'municipality' => 'Dumaguete City', 'barangay' => 'Bantayan',
                'province' => 'Negros Oriental', 'area_hectares' => 1,
                'area_square_meters' => 10000, 'status' => 'active',
            ]);
            $parcelIds[] = $parcel->id;
            App\Models\Landholding::create([
                'landowner_id' => $transferor->id, 'parcel_id' => $parcel->id,
                'area_hectares' => 1, 'status' => 'active',
            ]);
            $attributes = [
                'application_code' => 'E2E-JOURNEY-'.$kind.$suffix,
                'transferor_name' => $transferor->full_name, 'transferee_name' => $transferee->full_name,
                'transferors' => [['name' => $transferor->full_name, 'landowner_id' => $transferor->id,
                    'parcel_shares' => []]],
                'transferees' => [['name' => $transferee->full_name, 'landowner_id' => $transferee->id,
                    'parcel_shares' => []]],
                'transferor_landowner_id' => $transferor->id, 'transferee_landowner_id' => $transferee->id,
                'municipality' => 'Dumaguete City', 'barangay' => 'Bantayan',
                'date_of_application' => today()->toDateString(), 'encoded_by' => $staff->id,
                'status' => $kind === 'APPROVAL' ? 'for_releasing' : 'pending_legal_review',
            ];
            if ($kind === 'APPROVAL') {
                // Seed the already-reviewed initial record; never bypass guards on persisted records.
                $attributes += [
                    'payment_order_reference' => 'OP-E2E-JOURNEY', 'payment_order_issued_at' => now(),
                    'or_number' => 'OR-E2E-JOURNEY', 'or_date' => today()->toDateString(),
                    'amount_paid' => config('dar_ltc.filing_fee', 2000),
                    'csw_reference' => 'CSW-E2E-JOURNEY', 'csw_completed_at' => now(),
                    'csw_prepared_by' => $staff->id,
                ];
            }
            $application = App\Models\LandTransferApplication::create($attributes);
            $applicationParcel = App\Models\ApplicationParcel::create([
                'land_transfer_application_id' => $application->id, 'parcel_id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code, 'title_no' => $parcel->title_no,
                'tax_decl_no' => $parcel->tax_decl_no, 'lot_number' => $parcel->lot_number,
                'area_hectares' => 1, 'area_square_meters' => 10000,
            ]);
            $application->forceFill([
                'transferors' => [['name' => $transferor->full_name, 'landowner_id' => $transferor->id,
                    'parcel_shares' => [(string) $applicationParcel->id => 1]]],
                'transferees' => [['name' => $transferee->full_name, 'landowner_id' => $transferee->id,
                    'parcel_shares' => [(string) $applicationParcel->id => 1]]],
            ])->save();
            app(App\Services\ApplicationPartyShareIntegrityService::class)->assertValid($application);
            if ($kind === 'APPROVAL') {
                foreach (App\Models\RequiredDocument::all() as $requirement) {
                    App\Models\ApplicationDocument::create([
                        'land_transfer_application_id' => $application->id,
                        'required_document_id' => $requirement->id,
                        'annex_reference' => 'Reviewed physical annex '.$requirement->id,
                        'document_reference_number' => 'E2E-ANNEX-'.$requirement->id,
                        'document_metadata' => ['date_issued' => today()->toDateString()],
                        'uploaded_by' => $staff->id,
                    ]);
                }
                $sources = app(App\Services\ClearanceDocumentSourceService::class)->candidates($application);
                $application->forceFill([
                    'ltc_form4_subject_land_findings' => ['ra6657_not_covered_not_tenanted_retained_area'],
                    'ltc_form4_recommendation_findings' => ['application_complete'],
                    'ltc_form4_recommendation_decision' => 'approval',
                    'ltc_form4_certified_at' => today()->toDateString(),
                    'ltc_form4_certifying_officer_name' => 'Journey Review Officer',
                    'ltc_title_document_id' => $sources['title']->first()?->id,
                    'ltc_transfer_document_id' => $sources['transfer']->first()?->id,
                ])->save();
            }
            $applications[strtolower($kind)] = $application->id;
        }
        $geometryParcel = App\Models\Parcel::create([
            'parcel_code' => 'E2E-JOURNEY-GEOMETRY'.$suffix, 'title_no' => 'T-JOURNEY-GEOMETRY'.$suffix,
            'municipality' => 'Dumaguete City', 'barangay' => 'Bantayan',
            'province' => 'Negros Oriental', 'area_hectares' => 1,
            'area_square_meters' => 10000, 'status' => 'active',
        ]);
        $parcelIds[] = $geometryParcel->id;
        return [
            'applications' => $applications, 'geometry_parcel_id' => $geometryParcel->id,
            'landowner_user_id' => $ownerUser->id, 'transferee_id' => $transferee->id,
            'parcels' => App\Models\Parcel::whereIn('id', $parcelIds)->orderBy('id')->get()->toArray(),
            'holdings' => App\Models\Landholding::whereIn('parcel_id', $parcelIds)->orderBy('id')->get()->toArray(),
        ];
    });
};
$baseline = ['desktop' => $prepare(''), 'phone' => $prepare('-PHONE')];

// Independent populated map/ownership records are read-only in every browser test.
$baseline['maps'] = Illuminate\Support\Facades\DB::transaction(function () use ($staff, $password): array {
    $user = App\Models\User::create([
        'username' => $staff->username.'.mapowner', 'name' => 'Map Landowner',
        'email' => $staff->username.'.mapowner@example.test', 'password' => $password,
        'role' => 'landowner', 'registration_status' => 'approved',
        'is_active' => true, 'must_change_password' => false,
    ]);
    $owner = App\Models\Landowner::create([
        'first_name' => 'Map', 'last_name' => 'Landowner', 'user_id' => $user->id,
        'province' => 'Negros Oriental',
    ]);
    $otherOwner = App\Models\Landowner::create([
        'first_name' => 'Private', 'last_name' => 'Map Owner', 'province' => 'Negros Oriental',
    ]);
    $ids = [];
    foreach (['A', 'B', 'PRIVATE'] as $index => $label) {
        $west = 123.308 + $index * .0002;
        $parcel = App\Models\Parcel::create([
            'parcel_code' => 'E2E-MAP-ROLE-'.$label, 'title_no' => 'T-MAP-ROLE-'.$label,
            'municipality' => 'Dumaguete City', 'barangay' => 'Bantayan',
            'province' => 'Negros Oriental', 'area_hectares' => 1, 'status' => 'active',
            'geometry_geojson' => ['type' => 'Polygon', 'coordinates' => [[
                [$west, 9.3064], [$west + .001, 9.3064], [$west + .001, 9.3072],
                [$west, 9.3072], [$west, 9.3064],
            ]]],
        ]);
        $ids[$label] = $parcel->id;
        App\Models\Landholding::create([
            'landowner_id' => $label === 'PRIVATE' ? $otherOwner->id : $owner->id,
            'parcel_id' => $parcel->id, 'area_hectares' => .75, 'status' => 'active',
        ]);
    }
    return [
        'ids' => $ids,
        'parcels' => App\Models\Parcel::whereIn('id', $ids)->orderBy('id')->get()->toArray(),
        'holdings' => App\Models\Landholding::whereIn('parcel_id', $ids)->orderBy('id')->get()->toArray(),
    ];
});
if (file_put_contents(storage_path('app/browser-journeys.json'), json_encode($baseline, JSON_THROW_ON_ERROR)) === false) {
    throw new RuntimeException('Could not save browser journey baseline.');
}
$exports = [
    'E2E_COMPLIANCE_APPLICATION_ID' => $baseline['desktop']['applications']['compliance'],
    'E2E_APPROVAL_APPLICATION_ID' => $baseline['desktop']['applications']['approval'],
    'E2E_GEOMETRY_PARCEL_ID' => $baseline['desktop']['geometry_parcel_id'],
    'E2E_PHONE_COMPLIANCE_APPLICATION_ID' => $baseline['phone']['applications']['compliance'],
    'E2E_PHONE_APPROVAL_APPLICATION_ID' => $baseline['phone']['applications']['approval'],
    'E2E_PHONE_GEOMETRY_PARCEL_ID' => $baseline['phone']['geometry_parcel_id'],
    'E2E_MAP_PARCEL_A_ID' => $baseline['maps']['ids']['A'],
    'E2E_MAP_PARCEL_B_ID' => $baseline['maps']['ids']['B'],
    'E2E_MAP_PRIVATE_PARCEL_ID' => $baseline['maps']['ids']['PRIVATE'],
    'E2E_JOURNEY_DATE' => today()->toDateString(),
];
foreach ($exports as $key => $value) {
    if (file_put_contents($environmentFile, "$key=$value\n", FILE_APPEND) === false) {
        throw new RuntimeException('Could not export browser journey fixtures.');
    }
}
echo "Dedicated workflow and geometry browser journey fixtures prepared.\n";
$completed = true;
