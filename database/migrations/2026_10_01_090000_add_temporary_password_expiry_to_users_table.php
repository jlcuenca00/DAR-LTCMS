<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('temporary_password_expires_at')
                ->nullable()
                ->after('password_changed_at');
        });

        // Existing forced-change credentials receive a bounded grace window
        // instead of remaining valid indefinitely after this hardening deploy.
        DB::table('users')
            ->where('must_change_password', true)
            ->whereNull('temporary_password_expires_at')
            ->update([
                'temporary_password_expires_at' => now()->addHours(24),
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('temporary_password_expires_at');
        });
    }
};
