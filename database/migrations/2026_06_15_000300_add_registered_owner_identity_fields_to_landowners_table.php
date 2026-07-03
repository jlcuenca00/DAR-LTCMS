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
        Schema::table('landowners', function (Blueprint $table) {
            if (! Schema::hasColumn('landowners', 'registered_owner_status')) {
                $table->string('registered_owner_status')->nullable();
            }

            if (! Schema::hasColumn('landowners', 'spouse_name')) {
                $table->string('spouse_name')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landowners', function (Blueprint $table) {
            if (Schema::hasColumn('landowners', 'spouse_name')) {
                $table->dropColumn('spouse_name');
            }

            if (Schema::hasColumn('landowners', 'registered_owner_status')) {
                $table->dropColumn('registered_owner_status');
            }
        });
    }
};
