<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('land_transfer_applications', 'transfer_nature')) {
                $table->string('transfer_nature')->nullable()->after('date_of_transfer');
            }

            if (! Schema::hasColumn('land_transfer_applications', 'is_succession_case')) {
                $table->boolean('is_succession_case')->default(false)->after('transfer_nature');
            }

            if (! Schema::hasColumn('land_transfer_applications', 'retention_certificate_required')) {
                $table->boolean('retention_certificate_required')->default(false)->after('is_succession_case');
            }

            if (! Schema::hasColumn('land_transfer_applications', 'retention_certificate_reference')) {
                $table->string('retention_certificate_reference')->nullable()->after('retention_certificate_required');
            }

            if (! Schema::hasColumn('land_transfer_applications', 'landholding_review_notes')) {
                $table->text('landholding_review_notes')->nullable()->after('retention_certificate_reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            $columns = [
                'landholding_review_notes',
                'retention_certificate_reference',
                'retention_certificate_required',
                'is_succession_case',
                'transfer_nature',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('land_transfer_applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
