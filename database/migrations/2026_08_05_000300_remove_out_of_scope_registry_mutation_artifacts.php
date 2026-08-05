<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DAR-LTCMS is a clearance processing and monitoring system only.
        // Remove obsolete schema that could imply automatic ownership transfer.
        Schema::dropIfExists('landholding_mutations');

        if (Schema::hasTable('land_transfer_applications')) {
            DB::statement('ALTER TABLE land_transfer_applications DROP COLUMN IF EXISTS registry_mutated_by');
            DB::statement('ALTER TABLE land_transfer_applications DROP COLUMN IF EXISTS registry_mutated_at');
        }
    }

    public function down(): void
    {
        // Intentionally does not restore automatic mutation artifacts because
        // they are outside the approved operational scope of DAR-LTCMS.
    }
};
