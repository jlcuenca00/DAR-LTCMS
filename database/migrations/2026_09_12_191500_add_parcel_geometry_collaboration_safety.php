<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->unsignedBigInteger('geometry_version')->default(0)->after('geometry_geojson');
        });

        Schema::create('parcel_geometry_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcel_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('geometry_version');
            $table->json('geometry_geojson');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 50)->default('geodetic_edit');
            $table->timestamps();

            $table->unique(['parcel_id', 'geometry_version']);
            $table->index(['parcel_id', 'created_at']);
        });

        Schema::create('parcel_geometry_edit_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('session_token')->unique();
            $table->unsignedBigInteger('base_geometry_version')->default(0);
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['parcel_id', 'user_id']);
            $table->index(['parcel_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcel_geometry_edit_sessions');
        Schema::dropIfExists('parcel_geometry_revisions');

        Schema::table('parcels', function (Blueprint $table) {
            $table->dropColumn('geometry_version');
        });
    }
};
