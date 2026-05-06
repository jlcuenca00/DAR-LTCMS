<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyRecord extends Model
{
    public const TYPE_TITLE = 'title';
    public const TYPE_LANDHOLDING = 'landholding';
    public const TYPE_PARCEL_SOURCE = 'parcel_source';
    public const TYPE_HISTORICAL_CLEARANCE = 'historical_clearance';

    public const ORIGIN_MIGRATED = 'migrated';
    public const ORIGIN_ENCODED = 'encoded';
    public const ORIGIN_IMPORTED = 'imported';

    public const SOURCE_SCOPE_CURRENT_ACTIVE = 'current_active';
    public const SOURCE_SCOPE_HISTORICAL = 'historical';
    public const SOURCE_SCOPE_REFERENCE_ONLY = 'reference_only';

    public const RECORD_TYPES = [
        self::TYPE_TITLE => 'Title',
        self::TYPE_LANDHOLDING => 'Landholding',
        self::TYPE_PARCEL_SOURCE => 'Parcel Source',
        self::TYPE_HISTORICAL_CLEARANCE => 'Historical Clearance',
    ];

    public const ORIGINS = [
        self::ORIGIN_MIGRATED => 'Migrated',
        self::ORIGIN_ENCODED => 'Encoded',
        self::ORIGIN_IMPORTED => 'Imported',
    ];

    public const SOURCE_SCOPES = [
        self::SOURCE_SCOPE_CURRENT_ACTIVE => 'Current / Active Source Record',
        self::SOURCE_SCOPE_HISTORICAL => 'Historical Source Record',
        self::SOURCE_SCOPE_REFERENCE_ONLY => 'Reference Only / Unmatched',
    ];

    protected $fillable = [
        'record_type',
        'origin',
        'source_record_scope',
        'legacy_record_import_batch_id',
        'parcel_id',
        'encoded_by_user_id',

        'parcel_code',
        'title_number',
        'control_number',
        'application_reference_number',
        'tax_declaration_number',
        'lot_number',
        'survey_number',

        'landowner_name',
        'transferor_name',
        'transferee_name',

        'area_hectares',
        'crop_or_land_use',

        'barangay',
        'municipality',
        'province',
        'source_geometry_geojson',

        'record_date',
        'decision_status',
        'previous_dar_reference_number',
        'landholding_reference_number',

        'remarks',
        'boundary_description',

        'source_book',
        'page_number',
        'transcribed_by',
        'transcription_date',
        'source_notes',
        'source_record_package_id',
    ];

    protected $casts = [
        'record_date' => 'date',
        'transcription_date' => 'date',
        'area_hectares' => 'decimal:4',
        'source_geometry_geojson' => 'array',
    ];

    public function importBatch()
    {
        return $this->belongsTo(LegacyRecordImportBatch::class, 'legacy_record_import_batch_id');
    }

    public function encodedBy()
    {
        return $this->belongsTo(User::class, 'encoded_by_user_id');
    }

    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }

    public function getRecordTypeLabelAttribute(): string
    {
        return self::RECORD_TYPES[$this->record_type] ?? ucfirst(str_replace('_', ' ', $this->record_type));
    }

    public function getOriginLabelAttribute(): string
    {
        return self::ORIGINS[$this->origin] ?? ucfirst($this->origin);
    }

    public function getSourceRecordScopeLabelAttribute(): string
    {
        return self::SOURCE_SCOPES[$this->source_record_scope]
            ?? ucfirst(str_replace('_', ' ', $this->source_record_scope));
    }
    public function package()
{
    return $this->belongsTo(SourceRecordPackage::class, 'source_record_package_id');
}
}