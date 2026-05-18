<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class BarebonesTesterSeeder extends Seeder
{
    /**
     * Reset the system into a tester-ready barebones state.
     *
     * Result:
     * - clears demo/transactional records
     * - re-seeds the required document reference list
     * - keeps only one active staff account for the tester to start encoding data
     *
     * This does not create landowner, geodetic, parcel, landholding,
     * source record, application, clearance, notification, or audit demo data.
     */
    public function run(): void
    {
        $this->truncateApplicationData();

        $this->call(RequiredDocumentSeeder::class);

        User::create([
            'name' => 'DAR Staff Tester',
            'email' => 'staff.tester@dar-ltcms.local',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);
    }

    private function truncateApplicationData(): void
    {
        $tables = [
            // session/system runtime records
            'sessions',
            'password_reset_tokens',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',

            // notification/audit/output records
            'system_notifications',
            'audit_logs',
            'application_clearances',

            // application/document records
            'application_documents',
            'application_parcels',
            'land_transfer_applications',

            // source/legacy/import records
            'legacy_records',
            'legacy_record_import_batches',
            'source_record_packages',
            'source_record_package_import_batches',

            // landholding/parcel/landowner records
            'landholding_mutations',
            'landholdings',
            'parcels',
            'landowners',

            // users and static/reference list
            'users',
            'required_documents',
        ];

        $existingTables = array_values(array_filter(
            $tables,
            static fn (string $table): bool => Schema::hasTable($table)
        ));

        if ($existingTables === []) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $quotedTables = collect($existingTables)
                ->map(static fn (string $table): string => '"' . str_replace('"', '""', $table) . '"')
                ->implode(', ');

            DB::statement("TRUNCATE TABLE {$quotedTables} RESTART IDENTITY CASCADE");

            return;
        }

        Schema::disableForeignKeyConstraints();

        foreach ($existingTables as $table) {
            DB::table($table)->delete();
        }

        Schema::enableForeignKeyConstraints();
    }
}
