<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add LTC Form No. 4 attestation/recommendation review fields.
     *
     * These fields support administrative review and recommendation only.
     * They do not transfer ownership, mutate parcel ownership, or alter Registry of Deeds records.
     */
    public function up(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('land_transfer_applications', 'ltc_form4_subject_land_findings')) {
                $table->json('ltc_form4_subject_land_findings')->nullable();
            }

            if (! Schema::hasColumn('land_transfer_applications', 'ltc_form4_recommendation_findings')) {
                $table->json('ltc_form4_recommendation_findings')->nullable();
            }

            if (! Schema::hasColumn('land_transfer_applications', 'ltc_form4_recommendation_decision')) {
                $table->string('ltc_form4_recommendation_decision', 30)->nullable();
            }

            if (! Schema::hasColumn('land_transfer_applications', 'ltc_form4_other_findings')) {
                $table->text('ltc_form4_other_findings')->nullable();
            }

            if (! Schema::hasColumn('land_transfer_applications', 'ltc_form4_certified_at')) {
                $table->date('ltc_form4_certified_at')->nullable();
            }

            if (! Schema::hasColumn('land_transfer_applications', 'ltc_form4_certifying_officer_name')) {
                $table->string('ltc_form4_certifying_officer_name')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            $table->dropColumn([
                'ltc_form4_subject_land_findings',
                'ltc_form4_recommendation_findings',
                'ltc_form4_recommendation_decision',
                'ltc_form4_other_findings',
                'ltc_form4_certified_at',
                'ltc_form4_certifying_officer_name',
            ]);
        });
    }
};
