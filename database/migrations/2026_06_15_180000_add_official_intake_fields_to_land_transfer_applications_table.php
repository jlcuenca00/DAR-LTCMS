<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            $table->string('applicant_name')->nullable();
            $table->string('applicant_type', 50)->nullable();
            $table->string('authorized_representative_name')->nullable();
            $table->boolean('has_special_power_of_attorney')->default(false);
            $table->string('or_number', 100)->nullable();
            $table->date('or_date')->nullable();
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->date('date_of_application')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            $table->dropColumn([
                'applicant_name',
                'applicant_type',
                'authorized_representative_name',
                'has_special_power_of_attorney',
                'or_number',
                'or_date',
                'amount_paid',
                'date_of_application',
            ]);
        });
    }
};
