<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure one user account can only be linked to one landowner record.
     */
    public function up(): void
    {
        Schema::table('landowners', function (Blueprint $table) {
            $table->unique('user_id', 'landowners_user_id_unique');
        });
    }

    /**
     * Remove one-to-one account link constraint.
     */
    public function down(): void
    {
        Schema::table('landowners', function (Blueprint $table) {
            $table->dropUnique('landowners_user_id_unique');
        });
    }
};