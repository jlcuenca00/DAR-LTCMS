<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationComplianceNotice extends Model
{
    public const CATEGORY_MISSING_REQUIREMENT = 'missing_requirement';
    public const CATEGORY_INCORRECT_DOCUMENT = 'incorrect_incomplete_document';
    public const CATEGORY_ADDITIONAL_INFORMATION = 'additional_information';
    public const CATEGORY_PARCEL_LANDHOLDING = 'parcel_landholding_issue';
    public const CATEGORY_CLARIFICATION = 'clarification_needed';
    public const CATEGORY_OTHER = 'other';

    public const CATEGORIES = [
        self::CATEGORY_MISSING_REQUIREMENT,
        self::CATEGORY_INCORRECT_DOCUMENT,
        self::CATEGORY_ADDITIONAL_INFORMATION,
        self::CATEGORY_PARCEL_LANDHOLDING,
        self::CATEGORY_CLARIFICATION,
        self::CATEGORY_OTHER,
    ];

    protected $fillable = [
        'land_transfer_application_id',
        'category',
        'other_category',
        'details',
        'requested_items',
        'resume_status',
        'requested_by',
        'requested_at',
        'resolved_by',
        'resolved_at',
        'resolution_note',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_MISSING_REQUIREMENT => 'Missing Requirement',
            self::CATEGORY_INCORRECT_DOCUMENT => 'Incorrect / Incomplete Document',
            self::CATEGORY_ADDITIONAL_INFORMATION => 'Additional Information Needed',
            self::CATEGORY_PARCEL_LANDHOLDING => 'Parcel / Landholding Issue',
            self::CATEGORY_CLARIFICATION => 'Clarification Needed',
            self::CATEGORY_OTHER => 'Other',
        ];
    }

    public function categoryLabel(): string
    {
        if ($this->category === self::CATEGORY_OTHER && filled($this->other_category)) {
            return (string) $this->other_category;
        }

        return self::categoryOptions()[$this->category]
            ?? str((string) $this->category)->replace('_', ' ')->title()->toString();
    }

    public function application()
    {
        return $this->belongsTo(LandTransferApplication::class, 'land_transfer_application_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }
}
