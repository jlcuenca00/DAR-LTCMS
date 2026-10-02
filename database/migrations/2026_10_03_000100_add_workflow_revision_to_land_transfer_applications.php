<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            $table->unsignedBigInteger('workflow_revision')
                ->default(1)
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            $table->dropColumn('workflow_revision');
        });
    }
};
