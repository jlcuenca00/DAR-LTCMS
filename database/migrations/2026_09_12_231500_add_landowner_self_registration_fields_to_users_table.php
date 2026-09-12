<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->unique()->after('email_verified_at');
            }

            if (! Schema::hasColumn('users', 'auth_provider')) {
                $table->string('auth_provider')->default('local')->after('google_id');
            }

            if (! Schema::hasColumn('users', 'registration_status')) {
                $table->string('registration_status')->default('approved')->after('role');
            }

            if (! Schema::hasColumn('users', 'registration_notes')) {
                $table->text('registration_notes')->nullable()->after('registration_status');
            }

            if (! Schema::hasColumn('users', 'registration_reviewed_at')) {
                $table->timestamp('registration_reviewed_at')->nullable()->after('registration_notes');
            }

            if (! Schema::hasColumn('users', 'registration_reviewed_by_user_id')) {
                $table->foreignId('registration_reviewed_by_user_id')
                    ->nullable()
                    ->after('registration_reviewed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        DB::table('users')
            ->whereNull('registration_status')
            ->update(['registration_status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'registration_reviewed_by_user_id')) {
                $table->dropConstrainedForeignId('registration_reviewed_by_user_id');
            }

            foreach (['registration_reviewed_at', 'registration_notes', 'registration_status', 'auth_provider', 'google_id'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
