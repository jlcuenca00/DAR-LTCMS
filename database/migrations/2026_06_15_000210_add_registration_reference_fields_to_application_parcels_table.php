<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_parcels', function (Blueprint $table) {
            $table->string('lot_number')->nullable()->after('tax_decl_no');
            $table->string('survey_plan_number')->nullable()->after('lot_number');
            $table->string('title_type', 20)->nullable()->after('survey_plan_number');
            $table->string('rod_office')->nullable()->after('title_type');
            $table->decimal('area_square_meters', 14, 2)->nullable()->after('area_hectares');
        });
    }

    public function down(): void
    {
        Schema::table('application_parcels', function (Blueprint $table) {
            $table->dropColumn([
                'lot_number',
                'survey_plan_number',
                'title_type',
                'rod_office',
                'area_square_meters',
            ]);
        });
    }
};
