<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->after('name');
                $table->unique('username', 'users_username_unique');
            }
        });

        DB::table('users')->orderBy('id')->get()->each(function ($user) {
            if (filled($user->username ?? null)) return;
            $base = Str::slug(Str::before((string) ($user->email ?? $user->name ?? 'user'), '@'), '_') ?: 'user';
            $username = $base;
            $counter = 1;
            while (DB::table('users')->where('username', $username)->where('id', '<>', $user->id)->exists()) {
                $username = $base . '_' . (++$counter);
            }
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });

        try {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE users ALTER COLUMN email DROP NOT NULL');
            }
        } catch (Throwable $e) {
            // The app still stores a generated local placeholder email when none is provided.
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropUnique('users_username_unique');
                $table->dropColumn('username');
            }
        });
    }
};
