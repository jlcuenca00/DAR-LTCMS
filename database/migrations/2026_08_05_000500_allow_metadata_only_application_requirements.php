<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('application_documents')) {
            return;
        }

        // Requirement details may be encoded even when the supporting file is
        // not available. File fields therefore remain optional by design.
        DB::statement('ALTER TABLE application_documents ALTER COLUMN file_path DROP NOT NULL');
        DB::statement('ALTER TABLE application_documents ALTER COLUMN uploaded_by DROP NOT NULL');
    }

    public function down(): void
    {
        // This migration is intentionally not reversed automatically because
        // metadata-only requirement rows may exist after it is applied.
    }
};
