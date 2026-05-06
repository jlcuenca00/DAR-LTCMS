<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_record_import_batches', function (Blueprint $table) {
            $table->id();

            $table->string('record_type');
            $table->string('original_filename')->nullable();
            $table->string('status')->default('previewed');

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('committed_rows')->default(0);

            $table->foreignId('uploaded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('committed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('committed_at')->nullable();
            $table->json('summary')->nullable();

            $table->timestamps();

            $table->index(['record_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_record_import_batches');
    }
};