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
    Schema::table('land_transfer_applications', function (Blueprint $table) {

        $table->foreignId('transferee_landowner_id')
            ->nullable()
            ->after('transferee_name')
            ->constrained('landowners')
            ->nullOnDelete();

    });
}

public function down(): void
{
    Schema::table('land_transfer_applications', function (Blueprint $table) {

        $table->dropForeign(['transferee_landowner_id']);
        $table->dropColumn('transferee_landowner_id');

    });
}
};
