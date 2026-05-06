<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('legacy_records', 'parcel_id')) {
            Schema::table('legacy_records', function (Blueprint $table) {
                $table->foreignId('parcel_id')
                    ->nullable()
                    ->after('legacy_record_import_batch_id')
                    ->constrained('parcels')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('legacy_records', 'source_record_scope')) {
            Schema::table('legacy_records', function (Blueprint $table) {
                $table->string('source_record_scope')
                    ->default('current_active')
                    ->after('origin');
            });
        }

        if (! Schema::hasColumn('legacy_records', 'parcel_code')) {
            Schema::table('legacy_records', function (Blueprint $table) {
                $table->string('parcel_code')
                    ->nullable()
                    ->after('parcel_id');
            });
        }

        if (! Schema::hasColumn('legacy_records', 'source_geometry_geojson')) {
            Schema::table('legacy_records', function (Blueprint $table) {
                $table->longText('source_geometry_geojson')
                    ->nullable()
                    ->after('province');
            });
        }

        if (! Schema::hasColumn('legacy_records', 'landholding_reference_number')) {
            Schema::table('legacy_records', function (Blueprint $table) {
                $table->string('landholding_reference_number')
                    ->nullable()
                    ->after('previous_dar_reference_number');
            });
        }

        if (! Schema::hasColumn('legacy_records', 'application_reference_number')) {
            Schema::table('legacy_records', function (Blueprint $table) {
                $table->string('application_reference_number')
                    ->nullable()
                    ->after('control_number');
            });
        }

        if (! Schema::hasColumn('legacy_records', 'boundary_description')) {
            Schema::table('legacy_records', function (Blueprint $table) {
                $table->text('boundary_description')
                    ->nullable()
                    ->after('remarks');
            });
        }
    }

    public function down(): void
    {
        Schema::table('legacy_records', function (Blueprint $table) {
            if (Schema::hasColumn('legacy_records', 'boundary_description')) {
                $table->dropColumn('boundary_description');
            }

            if (Schema::hasColumn('legacy_records', 'application_reference_number')) {
                $table->dropColumn('application_reference_number');
            }

            if (Schema::hasColumn('legacy_records', 'landholding_reference_number')) {
                $table->dropColumn('landholding_reference_number');
            }

            if (Schema::hasColumn('legacy_records', 'source_geometry_geojson')) {
                $table->dropColumn('source_geometry_geojson');
            }

            if (Schema::hasColumn('legacy_records', 'parcel_code')) {
                $table->dropColumn('parcel_code');
            }

            if (Schema::hasColumn('legacy_records', 'source_record_scope')) {
                $table->dropColumn('source_record_scope');
            }

            if (Schema::hasColumn('legacy_records', 'parcel_id')) {
                $table->dropConstrainedForeignId('parcel_id');
            }
        });
    }
};