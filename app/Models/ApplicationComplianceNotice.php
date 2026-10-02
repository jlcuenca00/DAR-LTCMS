<?php

namespace App\Models;

use App\Services\ApplicationWorkflowRevisionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

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
        'requested_by_name_snapshot',
        'requested_at',
        'resolved_by',
        'resolved_by_name_snapshot',
        'resolved_at',
        'resolution_note',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    private bool $authorizedCreation = false;

    private bool $authorizedResolution = false;

    protected static function booted(): void
    {
        static::creating(function (ApplicationComplianceNotice $notice) {
            if (! $notice->authorizedCreation) {
                throw ValidationException::withMessages([
                    'compliance' => 'Compliance notices must be created through the guarded compliance workflow service.',
                ]);
            }

            $application = $notice->application()->first();
            if ($application && $application->isFinalized()) {
                throw ValidationException::withMessages([
                    'compliance' => 'Finalized applications cannot receive new compliance notices.',
                ]);
            }
        });

        static::updating(function (ApplicationComplianceNotice $notice) {
            $application = $notice->application()->first();

            if ($application && $application->isFinalized()) {
                throw ValidationException::withMessages([
                    'compliance' => 'Compliance history cannot be changed after the application is finalized.',
                ]);
            }

            if ($notice->getRawOriginal('resolved_at') !== null) {
                throw ValidationException::withMessages([
                    'compliance' => 'Resolved compliance notices are immutable.',
                ]);
            }

            if (! $notice->authorizedResolution) {
                throw ValidationException::withMessages([
                    'compliance' => 'Compliance notices may only be changed through the guarded resolution action.',
                ]);
            }

            $allowedFields = [
                'resolved_by',
                'resolved_by_name_snapshot',
                'resolved_at',
                'resolution_note',
                'updated_at',
            ];
            $dirtyFields = array_keys($notice->getDirty());
            $disallowed = array_values(array_diff($dirtyFields, $allowedFields));

            if ($disallowed !== []) {
                throw ValidationException::withMessages([
                    'compliance' => 'Original compliance-request details are immutable.',
                ]);
            }

            if (! $notice->resolved_at || ! $notice->resolved_by) {
                throw ValidationException::withMessages([
                    'compliance' => 'A compliance resolution must record the resolving user and timestamp.',
                ]);
            }
        });

        static::deleting(function () {
            throw ValidationException::withMessages([
                'compliance' => 'Persisted compliance history cannot be deleted.',
            ]);
        });

        static::saved(function (ApplicationComplianceNotice $notice) {
            if ($notice->land_transfer_application_id) {
                app(ApplicationWorkflowRevisionService::class)
                    ->bump((int) $notice->land_transfer_application_id);
            }
        });
    }

    public function runAuthorizedCreation(callable $callback): mixed
    {
        $this->authorizedCreation = true;

        try {
            return $callback();
        } finally {
            $this->authorizedCreation = false;
        }
    }

    public function runAuthorizedResolution(callable $callback): mixed
    {
        $this->authorizedResolution = true;

        try {
            return $callback();
        } finally {
            $this->authorizedResolution = false;
        }
    }


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
