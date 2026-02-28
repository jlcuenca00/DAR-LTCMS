<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('land_transfer_applications', function (Blueprint $table) {
        $table->text('decision_notes')->nullable();
        $table->string('decision_reason')->nullable();
        $table->timestamp('validated_at')->nullable();
        $table->json('validation_snapshot')->nullable();
    });
}

public function down(): void
{
    Schema::table('land_transfer_applications', function (Blueprint $table) {
        $table->dropColumn([
            'decision_notes',
            'decision_reason',
            'validated_at',
            'validation_snapshot',
        ]);
    });
}
};
