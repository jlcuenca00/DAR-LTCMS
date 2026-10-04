<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (! $app->environment('testing') || ! getenv('E2E_STAFF_USERNAME') || ! getenv('E2E_STAFF_PASSWORD')) {
    throw new RuntimeException('Geodetic browser fixtures require the isolated testing environment.');
}

App\Models\User::query()->updateOrCreate(
    ['username' => getenv('E2E_STAFF_USERNAME').'.geo'],
    [
        'name' => 'Geodetic Directory Verification',
        'email' => 'directory.geo@example.test',
        'password' => getenv('E2E_STAFF_PASSWORD'),
        'role' => 'geodetic',
        'is_active' => true,
        'must_change_password' => false,
        'temporary_password_expires_at' => null,
    ]
);

foreach (range(1, 25) as $n) {
    App\Models\Parcel::query()->updateOrCreate(
        ['parcel_code' => sprintf('E2E-DIR-%03d', $n)],
        [
            'municipality' => 'Dumaguete City', 'barangay' => 'Bantayan',
            'province' => 'Negros Oriental', 'area_hectares' => 1,
            'status' => 'active', 'geometry_geojson' => null,
        ]
    );
}
App\Models\Parcel::query()->updateOrCreate(
    ['parcel_code' => 'E2E-DIR-MAPPED'],
    [
        'municipality' => 'Dumaguete City', 'barangay' => 'Bantayan',
        'province' => 'Negros Oriental', 'area_hectares' => 1, 'status' => 'active',
        'geometry_geojson' => ['type' => 'Polygon', 'coordinates' => [[
            [123.30, 9.30], [123.31, 9.30], [123.31, 9.31], [123.30, 9.30],
        ]]],
    ]
);
App\Models\Parcel::query()->updateOrCreate(
    ['parcel_code' => 'E2E-DIR-ARCHIVED'],
    [
        'municipality' => 'Dumaguete City', 'barangay' => 'Bantayan',
        'province' => 'Negros Oriental', 'area_hectares' => 1,
        'status' => 'inactive', 'geometry_geojson' => null,
    ]
);
