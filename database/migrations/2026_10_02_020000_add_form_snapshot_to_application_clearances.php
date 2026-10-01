<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_clearances', function (Blueprint $table): void {
            $table->json('form_snapshot')->nullable()->after('parcel_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('application_clearances', function (Blueprint $table): void {
            $table->dropColumn('form_snapshot');
        });
    }
};
