<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            $table->text('latest_compliance_reason')->nullable();
            $table->timestamp('returned_for_compliance_at')->nullable();
            $table->foreignId('returned_for_compliance_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            $table->dropForeign(['returned_for_compliance_by']);
            $table->dropColumn([
                'latest_compliance_reason',
                'returned_for_compliance_at',
                'returned_for_compliance_by',
            ]);
        });
    }
};
