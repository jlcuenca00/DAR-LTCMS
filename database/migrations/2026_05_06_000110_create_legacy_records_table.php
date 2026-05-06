<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_records', function (Blueprint $table) {
            $table->id();

            $table->string('record_type');
            $table->string('origin')->default('migrated');

            $table->foreignId('legacy_record_import_batch_id')
                ->nullable()
                ->constrained('legacy_record_import_batches')
                ->nullOnDelete();

            $table->foreignId('encoded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('title_number')->nullable();
            $table->string('control_number')->nullable();
            $table->string('tax_declaration_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('survey_number')->nullable();

            $table->string('landowner_name')->nullable();
            $table->string('transferor_name')->nullable();
            $table->string('transferee_name')->nullable();

            $table->decimal('area_hectares', 12, 4)->nullable();
            $table->string('crop_or_land_use')->nullable();

            $table->string('barangay')->nullable();
            $table->string('municipality')->nullable();
            $table->string('province')->default('Negros Oriental');

            $table->date('record_date')->nullable();
            $table->string('decision_status')->nullable();
            $table->string('previous_dar_reference_number')->nullable();

            $table->text('remarks')->nullable();

            $table->string('source_book');
            $table->string('page_number')->nullable();
            $table->string('transcribed_by');
            $table->date('transcription_date');
            $table->text('source_notes')->nullable();

            $table->timestamps();

            $table->index(['record_type', 'origin']);
            $table->index(['municipality', 'barangay']);
            $table->index('title_number');
            $table->index('control_number');
            $table->index('tax_declaration_number');
            $table->index('lot_number');
        });

        DB::statement("
            CREATE UNIQUE INDEX legacy_records_unique_title_number
            ON legacy_records (record_type, lower(title_number))
            WHERE title_number IS NOT NULL AND record_type = 'title'
        ");

        DB::statement("
            CREATE UNIQUE INDEX legacy_records_unique_control_number
            ON legacy_records (record_type, lower(control_number))
            WHERE control_number IS NOT NULL AND record_type = 'historical_clearance'
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS legacy_records_unique_control_number');
        DB::statement('DROP INDEX IF EXISTS legacy_records_unique_title_number');

        Schema::dropIfExists('legacy_records');
    }
};