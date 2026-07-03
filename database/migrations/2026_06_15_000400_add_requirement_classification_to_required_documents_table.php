<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('required_documents', function (Blueprint $table) {
            $table->string('requirement_classification', 40)
                ->default('mandatory')
                ->after('is_mandatory');

            $table->boolean('blocks_acceptance')
                ->default(true)
                ->after('requirement_classification');

            $table->text('classification_notes')
                ->nullable()
                ->after('blocks_acceptance');
        });

        DB::table('required_documents')->update([
            'requirement_classification' => 'mandatory',
            'blocks_acceptance' => true,
        ]);

        DB::table('required_documents')
            ->where('is_mandatory', false)
            ->update([
                'requirement_classification' => 'case_dependent',
                'blocks_acceptance' => false,
            ]);

        DB::table('required_documents')
            ->whereRaw('LOWER(name) LIKE ?', ['%tax declaration%'])
            ->update([
                'is_mandatory' => false,
                'requirement_classification' => 'reference',
                'blocks_acceptance' => false,
                'classification_notes' => 'Tax Declaration is supplemental/reference for clearance review and assessor classification context; it is not an automatic release blocker by itself.',
            ]);

        DB::table('required_documents')
            ->whereRaw('LOWER(name) LIKE ?', ['%death certificate%'])
            ->update([
                'is_mandatory' => false,
                'requirement_classification' => 'case_dependent',
                'blocks_acceptance' => false,
                'classification_notes' => 'Required only when deceased persons are indicated in the transfer instrument.',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('required_documents', function (Blueprint $table) {
            $table->dropColumn([
                'requirement_classification',
                'blocks_acceptance',
                'classification_notes',
            ]);
        });
    }
};
