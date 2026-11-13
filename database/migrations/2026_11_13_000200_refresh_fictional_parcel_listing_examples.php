<?php

use Database\Seeders\ParcelListingDemoSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->isProduction()) {
            app(ParcelListingDemoSeeder::class)->run(allowProduction: true, refreshExamples: true);
        }
    }

    public function down(): void
    {
        // The refreshed synthetic registry data is retained.
    }
};
