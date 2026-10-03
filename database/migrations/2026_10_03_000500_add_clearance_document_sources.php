<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            $table->foreignId('ltc_title_document_id')->nullable()->constrained('application_documents')->nullOnDelete();
            $table->foreignId('ltc_transfer_document_id')->nullable()->constrained('application_documents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('land_transfer_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ltc_title_document_id');
            $table->dropConstrainedForeignId('ltc_transfer_document_id');
        });
    }
};
