<?php

use App\Models\ParcelListing;
use Database\Seeders\ParcelListingDemoSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->isProduction()) {
            app(ParcelListingDemoSeeder::class)->run(allowProduction: true);
        }
    }

    public function down(): void
    {
        // Retain linked registry records and their audit history on rollback.
        ParcelListing::query()->where('is_demo', true)
            ->whereHas('landholding.parcel', fn ($parcel) => $parcel
                ->whereIn('parcel_code', array_map(fn ($number) => sprintf('DEMO-LISTING-%02d', $number), range(1, 15))))
            ->update(['status' => ParcelListing::WITHDRAWN]);
    }
};
