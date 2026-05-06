<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SourceRecordPackage extends Model
{
    public const STATUS_ENCODED = 'encoded';
    public const STATUS_LINKED = 'linked';
    public const STATUS_PARCEL_CREATED = 'parcel_created';

    public const STATUSES = [
        self::STATUS_ENCODED => 'Encoded',
        self::STATUS_LINKED => 'Linked to Parcel',
        self::STATUS_PARCEL_CREATED => 'Parcel Created',
    ];

    protected $fillable = [
        'package_code',
        'status',
        'source_record_scope',
        'parcel_id',
        'encoded_by_user_id',

        'parcel_code',
        'title_number',
        'landholding_reference_number',
        'control_number',

        'landowner_name',
        'transferor_name',
        'transferee_name',

        'lot_number',
        'survey_number',
        'area_hectares',
        'crop_or_land_use',

        'barangay',
        'municipality',
        'province',

        'source_geometry_geojson',
        'boundary_description',

        'source_book',
        'page_number',
        'transcribed_by',
        'transcription_date',
        'source_notes',
        'remarks',
    ];

    protected $casts = [
        'area_hectares' => 'decimal:4',
        'source_geometry_geojson' => 'array',
        'transcription_date' => 'date',
    ];

    public function records()
    {
        return $this->hasMany(LegacyRecord::class);
    }

    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }

    public function encodedBy()
    {
        return $this->belongsTo(User::class, 'encoded_by_user_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getSourceRecordScopeLabelAttribute(): string
    {
        return LegacyRecord::SOURCE_SCOPES[$this->source_record_scope]
            ?? ucfirst(str_replace('_', ' ', $this->source_record_scope));
    }
}