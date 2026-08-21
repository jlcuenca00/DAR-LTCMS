<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_parcels', function (Blueprint $table) {
            $table->unique(
                ['land_transfer_application_id', 'parcel_id'],
                'application_parcels_application_parcel_unique'
            );
        });

        Schema::table('landholdings', function (Blueprint $table) {
            $table->dropForeign(['landowner_id']);
            $table->dropForeign(['parcel_id']);
        });

        Schema::table('landholdings', function (Blueprint $table) {
            $table->foreign('landowner_id')
                ->references('id')
                ->on('landowners')
                ->restrictOnDelete();

            $table->foreign('parcel_id')
                ->references('id')
                ->on('parcels')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('landholdings', function (Blueprint $table) {
            $table->dropForeign(['landowner_id']);
            $table->dropForeign(['parcel_id']);
        });

        Schema::table('landholdings', function (Blueprint $table) {
            $table->foreign('landowner_id')
                ->references('id')
                ->on('landowners')
                ->cascadeOnDelete();

            $table->foreign('parcel_id')
                ->references('id')
                ->on('parcels')
                ->cascadeOnDelete();
        });

        Schema::table('application_parcels', function (Blueprint $table) {
            $table->dropUnique('application_parcels_application_parcel_unique');
        });
    }
};
