<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_record_packages', function (Blueprint $table) {
            $table->id();

            $table->string('package_code')->unique();
            $table->string('status')->default('encoded');
            $table->string('source_record_scope')->default('current_active');

            $table->foreignId('parcel_id')
                ->nullable()
                ->constrained('parcels')
                ->nullOnDelete();

            $table->foreignId('encoded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('parcel_code')->nullable();
            $table->string('title_number')->nullable();
            $table->string('landholding_reference_number')->nullable();
            $table->string('control_number')->nullable();

            $table->string('landowner_name')->nullable();
            $table->string('transferor_name')->nullable();
            $table->string('transferee_name')->nullable();

            $table->string('lot_number')->nullable();
            $table->string('survey_number')->nullable();
            $table->decimal('area_hectares', 12, 4)->nullable();
            $table->string('crop_or_land_use')->nullable();

            $table->string('barangay')->nullable();
            $table->string('municipality')->nullable();
            $table->string('province')->default('Negros Oriental');

            $table->longText('source_geometry_geojson')->nullable();
            $table->text('boundary_description')->nullable();

            $table->string('source_book');
            $table->string('page_number')->nullable();
            $table->string('transcribed_by');
            $table->date('transcription_date');
            $table->text('source_notes')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index(['status', 'source_record_scope']);
            $table->index('parcel_id');
            $table->index('parcel_code');
            $table->index('title_number');
            $table->index('landholding_reference_number');
            $table->index('control_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_record_packages');
    }
};