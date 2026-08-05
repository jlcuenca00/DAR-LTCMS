<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('land_transfer_applications', 'transferors')) {
                $table->jsonb('transferors')->nullable()->after('transferor_name');
            }
            if (! Schema::hasColumn('land_transfer_applications', 'transferees')) {
                $table->jsonb('transferees')->nullable()->after('transferee_name');
            }
            if (! Schema::hasColumn('land_transfer_applications', 'transfer_instruments')) {
                $table->jsonb('transfer_instruments')->nullable()->after('transfer_nature');
            }
            if (! Schema::hasColumn('land_transfer_applications', 'date_of_clearance_release')) {
                $table->date('date_of_clearance_release')->nullable()->after('date_of_transfer');
            }
            if (! Schema::hasColumn('land_transfer_applications', 'ltc_page_number')) {
                $table->unsignedInteger('ltc_page_number')->default(1)->after('date_of_clearance_release');
            }
        });
    }

    public function down(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            foreach (['transferors', 'transferees', 'transfer_instruments', 'date_of_clearance_release', 'ltc_page_number'] as $column) {
                if (Schema::hasColumn('land_transfer_applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
