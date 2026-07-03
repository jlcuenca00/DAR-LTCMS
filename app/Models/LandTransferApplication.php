<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandTransferApplication extends Model
{
    /**
     * Revised DAR office workflow statuses.
     *
     * Important scope rule:
     * These statuses only track clearance processing and final decision output.
     * They must never trigger automatic land ownership transfer or registry mutation.
     */
    public const STATUS_PENDING_LEGAL_REVIEW = 'pending_legal_review';
    public const STATUS_ENDORSED_LTI = 'endorsed_lti';
    public const STATUS_ENDORSED_CHIEF_LEGAL = 'endorsed_chief_legal';
    public const STATUS_ENDORSED_PARPO = 'endorsed_parpo';
    public const STATUS_FOR_RELEASING = 'for_releasing';
    public const STATUS_RELEASED = 'released';
    public const STATUS_DENIED = 'denied';

    /**
     * Legacy statuses kept temporarily so older records/tests do not crash during
     * the phased DAR flow revision. Do not use these for new workflow code.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_NOT_APPROVED = 'not_approved';

    public const FINAL_STATUSES = [
        self::STATUS_RELEASED,
        self::STATUS_DENIED,
    ];

    public const LEGACY_FINAL_STATUSES = [
        self::STATUS_APPROVED,
        self::STATUS_NOT_APPROVED,
    ];

    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING_LEGAL_REVIEW,
        self::STATUS_ENDORSED_LTI,
        self::STATUS_ENDORSED_CHIEF_LEGAL,
        self::STATUS_ENDORSED_PARPO,
        self::STATUS_FOR_RELEASING,
    ];

    protected $fillable = [
        'application_code',
        'applicant_name',
        'applicant_type',
        'authorized_representative_name',
        'has_special_power_of_attorney',
        'or_number',
        'or_date',
        'amount_paid',
        'date_of_application',
        'transfer_nature',
        'is_succession_case',
        'retention_certificate_required',
        'retention_certificate_reference',
        'landholding_review_notes',
        'transferor_name',
        'transferee_name',
        'barangay',
        'municipality',
        'status',
        'encoded_by',
        'reviewed_by',
        'reviewed_at',
        'decision_reason',
        'decision_notes',
        'validated_at',
        'validation_snapshot',

        'transferor_landowner_id',
        'transferee_landowner_id',
        'registry_mutated_at',
        'registry_mutated_by',
    ];

    protected $casts = [
        'ltc_form4_subject_land_findings' => 'array',
        'ltc_form4_recommendation_findings' => 'array',
        'ltc_form4_certified_at' => 'date',
        'reviewed_at' => 'datetime',
        'date_of_application' => 'date',
        'or_date' => 'date',
        'amount_paid' => 'decimal:2',
        'has_special_power_of_attorney' => 'boolean',
        'is_succession_case' => 'boolean',
        'retention_certificate_required' => 'boolean',
        'validated_at' => 'datetime',
        'registry_mutated_at' => 'datetime',
        'validation_snapshot' => 'array',
    ];

    public function isFinalized(): bool
    {
        return in_array($this->status, array_merge(self::FINAL_STATUSES, self::LEGACY_FINAL_STATUSES), true);
    }

    public function isEditable(): bool
    {
        return ! $this->isFinalized();
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING_LEGAL_REVIEW => 'Pending Review by Legal Officer',
            self::STATUS_ENDORSED_LTI => 'Endorsed to LTI Division',
            self::STATUS_ENDORSED_CHIEF_LEGAL => 'Endorsed to Chief Legal',
            self::STATUS_ENDORSED_PARPO => 'Endorsed to PARPO II',
            self::STATUS_FOR_RELEASING => 'For Releasing',
            self::STATUS_RELEASED => 'Released',
            self::STATUS_DENIED => 'Denied',

            // Legacy record display mapping during the phased DAR flow revision.
            // These labels prevent old database rows from showing outdated wording.
            self::STATUS_DRAFT => 'Pending Review by Legal Officer',
            self::STATUS_PENDING_REVIEW => 'Pending Review by Legal Officer',
            self::STATUS_APPROVED => 'Released',
            self::STATUS_NOT_APPROVED => 'Denied',
        ];
    }

    public static function workflowStatusOptions(): array
    {
        return [
            self::STATUS_PENDING_LEGAL_REVIEW => 'Pending Review by Legal Officer',
            self::STATUS_ENDORSED_LTI => 'Endorsed to LTI Division',
            self::STATUS_ENDORSED_CHIEF_LEGAL => 'Endorsed to Chief Legal',
            self::STATUS_ENDORSED_PARPO => 'Endorsed to PARPO II',
            self::STATUS_FOR_RELEASING => 'For Releasing',
            self::STATUS_RELEASED => 'Released',
            self::STATUS_DENIED => 'Denied',
        ];
    }

    public static function transferNatureOptions(): array
    {
        return [
            'sale' => 'Sale',
            'donation' => 'Donation',
            'succession' => 'Succession / inheritance',
            'extrajudicial_settlement' => 'Extrajudicial settlement',
            'waiver_of_rights' => 'Waiver of rights',
            'other' => 'Other transfer instrument',
        ];
    }

    public function transferNatureLabel(): string
    {
        return self::transferNatureOptions()[$this->transfer_nature] ?? 'Not specified';
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? str($this->status)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public static function workflowTransitions(): array
    {
        return [
            self::STATUS_PENDING_LEGAL_REVIEW => self::STATUS_ENDORSED_LTI,
            self::STATUS_ENDORSED_LTI => self::STATUS_ENDORSED_CHIEF_LEGAL,
            self::STATUS_ENDORSED_CHIEF_LEGAL => self::STATUS_ENDORSED_PARPO,
            self::STATUS_ENDORSED_PARPO => self::STATUS_FOR_RELEASING,
            self::STATUS_FOR_RELEASING => self::STATUS_RELEASED,
        ];
    }

    public function nextWorkflowStatus(): ?string
    {
        return self::workflowTransitions()[$this->status] ?? null;
    }

    public function documents()
    {
        return $this->hasMany(ApplicationDocument::class, 'land_transfer_application_id');
    }

    public function applicationParcels()
    {
        return $this->hasMany(ApplicationParcel::class, 'land_transfer_application_id');
    }

    public function transferorLandowner()
    {
        return $this->belongsTo(Landowner::class, 'transferor_landowner_id');
    }

    public function transfereeLandowner()
    {
        return $this->belongsTo(Landowner::class, 'transferee_landowner_id');
    }

    public function clearance()
    {
        return $this->hasOne(ApplicationClearance::class, 'land_transfer_application_id');
    }
}
