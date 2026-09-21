<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 3);

require $root . '/vendor/autoload.php';

$app = require $root . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! app()->environment('production')) {
    throw new RuntimeException('Refusing to refresh demo data outside the production environment.');
}

$seedPath = __DIR__ . '/demo-refresh.sql';
$sql = file_get_contents($seedPath);

if ($sql === false || $sql === '') {
    throw new RuntimeException('The uploaded demo refresh SQL file is missing or empty.');
}

if (! str_contains($sql, 'CREATE TEMP TABLE dar_demo_application_ids')) {
    throw new RuntimeException('The demo refresh SQL is missing the safe demo-record targeting guard.');
}

if (str_contains($sql, "application_code LIKE '2026-DGT-%'")) {
    throw new RuntimeException('Unsafe broad 2026-DGT application cleanup detected.');
}

$beforeParcels = DB::table('parcels')
    ->where('parcel_code', 'like', 'DGT-AGRI-%')
    ->count();

$beforeApplications = DB::table('land_transfer_applications')
    ->where(function ($query) {
        $query->where('application_code', 'like', '2026-DGT-DEMO-%')
            ->orWhereExists(function ($subquery) {
                $subquery->selectRaw('1')
                    ->from('application_parcels as ap')
                    ->join('parcels as p', 'p.id', '=', 'ap.parcel_id')
                    ->whereColumn('ap.land_transfer_application_id', 'land_transfer_applications.id')
                    ->where('p.parcel_code', 'like', 'DGT-AGRI-%');
            });
    })
    ->count();

echo "Existing targeted demo parcels: {$beforeParcels}\n";
echo "Existing targeted demo applications: {$beforeApplications}\n";

DB::unprepared($sql);

$parcelCount = DB::table('parcels')
    ->where('parcel_code', 'like', 'DGT-AGRI-%')
    ->count();

$applicationCount = DB::table('land_transfer_applications')
    ->where('application_code', 'like', '2026-DGT-DEMO-%')
    ->count();

$finalCount = DB::table('land_transfer_applications')
    ->where('application_code', 'like', '2026-DGT-DEMO-%')
    ->whereIn('status', ['approved', 'denied'])
    ->count();

$badPaymentCount = DB::table('land_transfer_applications')
    ->where('application_code', 'like', '2026-DGT-DEMO-%')
    ->whereNotNull('amount_paid')
    ->where('amount_paid', '<>', 2000)
    ->count();

$nonAgriculturalCount = DB::table('parcels')
    ->where('parcel_code', 'like', 'DGT-AGRI-%')
    ->where('agricultural_status', '<>', 'private_agricultural')
    ->count();

if ($parcelCount !== 16) {
    throw new RuntimeException("Expected 16 DGT-AGRI demo parcels after refresh; found {$parcelCount}.");
}

if ($applicationCount !== 16) {
    throw new RuntimeException("Expected 16 DGT demo applications after refresh; found {$applicationCount}.");
}

if ($finalCount !== 5) {
    throw new RuntimeException("Expected 5 final Approved/Denied demo applications; found {$finalCount}.");
}

if ($badPaymentCount !== 0) {
    throw new RuntimeException("Found {$badPaymentCount} demo applications with an incorrect recorded payment amount.");
}

if ($nonAgriculturalCount !== 0) {
    throw new RuntimeException("Found {$nonAgriculturalCount} demo parcels that are not marked Private Agricultural Land.");
}

echo "Production demo refresh completed successfully.\n";
echo "Parcels: {$parcelCount}; Applications: {$applicationCount}; Final decisions: {$finalCount}.\n";
