<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('application_parcels', function (Blueprint $table) {
        $table->foreign('parcel_id')
            ->references('id')
            ->on('parcels')
            ->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('application_parcels', function (Blueprint $table) {
        $table->dropForeign(['parcel_id']);
    });
}
};
