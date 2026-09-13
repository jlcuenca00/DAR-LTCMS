<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            $table->boolean('applicant_is_juridical_entity')->default(false)->after('applicant_type');
            $table->string('payment_order_reference', 150)->nullable()->after('has_special_power_of_attorney');
            $table->timestamp('payment_order_issued_at')->nullable()->after('payment_order_reference');

            $table->string('csw_reference', 150)->nullable()->after('landholding_review_notes');
            $table->timestamp('csw_completed_at')->nullable()->after('csw_reference');
            $table->unsignedBigInteger('csw_prepared_by')->nullable()->after('csw_completed_at');
            $table->text('csw_notes')->nullable()->after('csw_prepared_by');

            $table->string('release_status', 30)->default('not_ready')->after('status');
            $table->timestamp('ready_for_release_at')->nullable()->after('release_status');
            $table->timestamp('released_at')->nullable()->after('ready_for_release_at');
            $table->unsignedBigInteger('released_by')->nullable()->after('released_at');
            $table->string('release_recipient_name', 255)->nullable()->after('released_by');
            $table->string('release_logbook_reference', 150)->nullable()->after('release_recipient_name');
            $table->string('csm_status', 30)->nullable()->after('release_logbook_reference');
        });

        Schema::table('required_documents', function (Blueprint $table) {
            $table->string('condition_key', 60)->nullable()->after('blocks_acceptance');
            $table->unsignedSmallInteger('max_age_months')->nullable()->after('condition_key');
        });

        // Preserve historical meaning for records that were already treated as released.
        DB::table('land_transfer_applications')
            ->whereIn('status', ['released', 'approved'])
            ->update([
                'release_status' => 'released',
                'released_at' => DB::raw('COALESCE(reviewed_at, updated_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('required_documents', function (Blueprint $table) {
            $table->dropColumn(['condition_key', 'max_age_months']);
        });

        Schema::table('land_transfer_applications', function (Blueprint $table) {
            $table->dropColumn([
                'applicant_is_juridical_entity',
                'payment_order_reference',
                'payment_order_issued_at',
                'csw_reference',
                'csw_completed_at',
                'csw_prepared_by',
                'csw_notes',
                'release_status',
                'ready_for_release_at',
                'released_at',
                'released_by',
                'release_recipient_name',
                'release_logbook_reference',
                'csm_status',
            ]);
        });
    }
};
