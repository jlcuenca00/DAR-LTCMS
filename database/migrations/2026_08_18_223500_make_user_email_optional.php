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
            $table->string('email')->nullable()->change();
        });

        DB::table('users')
            ->whereRaw('LOWER(email) LIKE ?', ['%@dar-ltcms.local'])
            ->update([
                'email' => null,
                'email_verified_at' => null,
            ]);
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET email = username || '@dar-ltcms.local' WHERE email IS NULL");

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
