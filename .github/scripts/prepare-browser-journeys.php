<?php

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

$baseline = Illuminate\Support\Facades\DB::transaction(function () use ($staff, $password): array {
    $ownerUser = App\Models\User::create([
        'username' => $staff->username.'.owner', 'name' => 'Journey Landowner',
        'email' => $staff->username.'.owner@example.test', 'password' => $password,
        'role' => 'landowner', 'registration_status' => 'approved',
        'is_active' => true, 'must_change_password' => false,
    ]);
    App\Models\User::create([
        'username' => $staff->username.'.geo2', 'name' => 'Journey Second Geodetic',
        'email' => $staff->username.'.geo2@example.test', 'password' => $password,
        'role' => 'geodetic', 'is_active' => true, 'must_change_password' => false,
    ]);
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
            'parcel_code' => 'E2E-JOURNEY-'.$kind, 'title_no' => 'T-JOURNEY-'.$kind,
            'tax_decl_no' => 'TD-JOURNEY-'.$kind, 'lot_number' => 'LOT-JOURNEY-'.$kind,
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
            'application_code' => 'E2E-JOURNEY-'.$kind,
            'transferor_name' => $transferor->full_name, 'transferee_name' => $transferee->full_name,
            'transferors' => [['name' => $transferor->full_name, 'landowner_id' => $transferor->id,
                'parcel_shares' => [(string) $parcel->id => 1]]],
            'transferees' => [['name' => $transferee->full_name, 'landowner_id' => $transferee->id,
                'parcel_shares' => [(string) $parcel->id => 1]]],
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
        App\Models\ApplicationParcel::create([
            'land_transfer_application_id' => $application->id, 'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code, 'title_no' => $parcel->title_no,
            'tax_decl_no' => $parcel->tax_decl_no, 'lot_number' => $parcel->lot_number,
            'area_hectares' => 1, 'area_square_meters' => 10000,
        ]);
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
        'parcel_code' => 'E2E-JOURNEY-GEOMETRY', 'title_no' => 'T-JOURNEY-GEOMETRY',
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
if (file_put_contents(storage_path('app/browser-journeys.json'), json_encode($baseline, JSON_THROW_ON_ERROR)) === false) {
    throw new RuntimeException('Could not save browser journey baseline.');
}
$exports = [
    'E2E_COMPLIANCE_APPLICATION_ID' => $baseline['applications']['compliance'],
    'E2E_APPROVAL_APPLICATION_ID' => $baseline['applications']['approval'],
    'E2E_GEOMETRY_PARCEL_ID' => $baseline['geometry_parcel_id'],
    'E2E_JOURNEY_DATE' => today()->toDateString(),
];
foreach ($exports as $key => $value) {
    if (file_put_contents($environmentFile, "$key=$value\n", FILE_APPEND) === false) {
        throw new RuntimeException('Could not export browser journey fixtures.');
    }
}
echo "Dedicated workflow and geometry browser journey fixtures prepared.\n";
