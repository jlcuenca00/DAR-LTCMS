<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            if (! Schema::hasColumn('parcels', 'reference_photo_path')) {
                $table->string('reference_photo_path')->nullable()->after('remarks');
            }
        });

        Schema::table('landholdings', function (Blueprint $table) {
            if (! Schema::hasColumn('landholdings', 'reference_photo_path')) {
                $table->string('reference_photo_path')->nullable()->after('remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            if (Schema::hasColumn('parcels', 'reference_photo_path')) {
                $table->dropColumn('reference_photo_path');
            }
        });

        Schema::table('landholdings', function (Blueprint $table) {
            if (Schema::hasColumn('landholdings', 'reference_photo_path')) {
                $table->dropColumn('reference_photo_path');
            }
        });
    }
};
