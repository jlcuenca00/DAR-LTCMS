<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class LandTransferApplication extends Model
{
    /**
     * DAR A.O. No. 4, s. 2021 administrative workflow statuses.
     *
     * These statuses track clearance processing only. They never execute land
     * ownership transfer, landholding mutation, or Registry of Deeds changes.
     */
    public const STATUS_PENDING_LEGAL_REVIEW = 'pending_legal_review';
    public const STATUS_RETURNED_FOR_COMPLIANCE = 'returned_for_compliance';
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_ENDORSED_LTI = 'endorsed_lti';
    public const STATUS_RETURNED_TO_LEGAL = 'returned_to_legal';
    public const STATUS_LEGAL_EVALUATION = 'legal_evaluation';
    public const STATUS_ENDORSED_CHIEF_LEGAL = 'endorsed_chief_legal';
    public const STATUS_ENDORSED_PARPO = 'endorsed_parpo';
    public const STATUS_FOR_RELEASING = 'for_releasing'; // PARPO II decision ready for Legal recording
    public const STATUS_APPROVED = 'approved';
    public const STATUS_NOT_APPROVED = 'not_approved';

    /**
     * Legacy values retained so historical records remain readable.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_RELEASED = 'released';
    public const STATUS_DENIED = 'denied';

    public const RELEASE_NOT_READY = 'not_ready';
    public const RELEASE_READY = 'ready_for_release';
    public const RELEASED_TO_CLIENT = 'released';

    public const FINAL_DECISION_AUTHORITY = 'PARPO II';

    public const FINAL_STATUSES = [
        self::STATUS_APPROVED,
        self::STATUS_NOT_APPROVED,
    ];

    public const LEGACY_FINAL_STATUSES = [
        self::STATUS_RELEASED,
        self::STATUS_DENIED,
    ];

    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING_LEGAL_REVIEW,
        self::STATUS_RETURNED_FOR_COMPLIANCE,
        self::STATUS_AWAITING_PAYMENT,
        self::STATUS_ENDORSED_LTI,
        self::STATUS_RETURNED_TO_LEGAL,
        self::STATUS_LEGAL_EVALUATION,
        self::STATUS_ENDORSED_CHIEF_LEGAL,
        self::STATUS_ENDORSED_PARPO,
        self::STATUS_FOR_RELEASING,
    ];

    protected $fillable = [
        'application_code',
        'applicant_name',
        'applicant_type',
        'applicant_is_juridical_entity',
        'authorized_representative_name',
        'has_special_power_of_attorney',
        'payment_order_reference',
        'payment_order_issued_at',
        'or_number',
        'or_date',
        'amount_paid',
        'date_of_application',
        'transfer_nature',
        'transfer_instruments',
        'is_succession_case',
        'retention_certificate_required',
        'retention_certificate_reference',
        'landholding_review_notes',
        'csw_reference',
        'csw_completed_at',
        'csw_prepared_by',
        'csw_notes',
        'transferor_name',
        'transferors',
        'transferee_name',
        'transferees',
        'barangay',
        'municipality',
        'date_filed',
        'date_of_transfer',
        'remarks',
        'date_of_clearance_release',
        'ltc_page_number',
        'status',
        'release_status',
        'ready_for_release_at',
        'released_at',
        'released_by',
        'release_recipient_name',
        'release_logbook_reference',
        'csm_status',
        'encoded_by',
        'reviewed_by',
        'reviewed_at',
        'decision_reason',
        'decision_notes',
        'decision_authority',
        'decision_officer_name',
        'decision_date',
        'decision_recorded_by',
        'decision_recorded_at',
        'validated_at',
        'validation_snapshot',
        'transferor_landowner_id',
        'transferee_landowner_id',
    ];

    protected $casts = [
        'ltc_form4_subject_land_findings' => 'array',
        'ltc_form4_recommendation_findings' => 'array',
        'ltc_form4_certified_at' => 'date',
        'transferors' => 'array',
        'transferees' => 'array',
        'transfer_instruments' => 'array',
        'date_filed' => 'date',
        'date_of_transfer' => 'date',
        'date_of_clearance_release' => 'date',
        'reviewed_at' => 'datetime',
        'decision_date' => 'date',
        'decision_recorded_at' => 'datetime',
        'date_of_application' => 'date',
        'payment_order_issued_at' => 'datetime',
        'or_date' => 'date',
        'amount_paid' => 'decimal:2',
        'has_special_power_of_attorney' => 'boolean',
        'applicant_is_juridical_entity' => 'boolean',
        'is_succession_case' => 'boolean',
        'retention_certificate_required' => 'boolean',
        'csw_completed_at' => 'datetime',
        'ready_for_release_at' => 'datetime',
        'released_at' => 'datetime',
        'validated_at' => 'datetime',
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

    public function isReleaseReady(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES, true)
            && $this->release_status === self::RELEASE_READY;
    }

    public function isReleasedToClient(): bool
    {
        return $this->release_status === self::RELEASED_TO_CLIENT
            || $this->status === self::STATUS_RELEASED;
    }

    public function canEditForm4(): bool
    {
        return ! $this->isFinalized() && in_array($this->status, [
            self::STATUS_ENDORSED_LTI,
            self::STATUS_RETURNED_TO_LEGAL,
        ], true);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING_LEGAL_REVIEW => 'Legal Completeness Review',
            self::STATUS_RETURNED_FOR_COMPLIANCE => 'Returned for Compliance',
            self::STATUS_AWAITING_PAYMENT => 'Awaiting Payment / Official Receipt',
            self::STATUS_ENDORSED_LTI => 'With LTID for Verification',
            self::STATUS_RETURNED_TO_LEGAL => 'Returned to Legal Division',
            self::STATUS_LEGAL_EVALUATION => 'Legal Evaluation / CSW Preparation',
            self::STATUS_ENDORSED_CHIEF_LEGAL => 'With Chief Legal for Review',
            self::STATUS_ENDORSED_PARPO => 'With PARPO II for Decision',
            self::STATUS_FOR_RELEASING => 'PARPO II Decision Ready to Record',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_NOT_APPROVED => 'Not Approved',

            // Historical compatibility only.
            self::STATUS_DRAFT => 'Legal Completeness Review',
            self::STATUS_PENDING_REVIEW => 'Legal Completeness Review',
            self::STATUS_RELEASED => 'Released (Legacy Record)',
            self::STATUS_DENIED => 'Not Approved (Legacy Record)',
        ];
    }

    public static function workflowStatusOptions(): array
    {
        return [
            self::STATUS_PENDING_LEGAL_REVIEW => 'Legal Completeness Review',
            self::STATUS_RETURNED_FOR_COMPLIANCE => 'Returned for Compliance',
            self::STATUS_AWAITING_PAYMENT => 'Awaiting Payment / Official Receipt',
            self::STATUS_ENDORSED_LTI => 'With LTID for Verification',
            self::STATUS_RETURNED_TO_LEGAL => 'Returned to Legal Division',
            self::STATUS_LEGAL_EVALUATION => 'Legal Evaluation / CSW Preparation',
            self::STATUS_ENDORSED_CHIEF_LEGAL => 'With Chief Legal for Review',
            self::STATUS_ENDORSED_PARPO => 'With PARPO II for Decision',
            self::STATUS_FOR_RELEASING => 'PARPO II Decision Ready to Record',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_NOT_APPROVED => 'Not Approved',
        ];
    }

    public static function releaseStatusLabels(): array
    {
        return [
            self::RELEASE_NOT_READY => 'Decision Output Pending Return to Legal',
            self::RELEASE_READY => 'Ready for Release',
            self::RELEASED_TO_CLIENT => 'Released to Client',
        ];
    }

    public function releaseStatusLabel(): string
    {
        return self::releaseStatusLabels()[$this->release_status ?? self::RELEASE_NOT_READY]
            ?? str((string) $this->release_status)->replace('_', ' ')->title()->toString();
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

    public static function form4SubjectLandOptions(): array
    {
        return [
            'pd27_not_covered_tenanted_retained_area' => 'Not covered by P.D. No. 27/E.O. No. 228 — Tenanted retained area',
            'pd27_not_covered_not_tenanted_retained_area' => 'Not covered by P.D. No. 27/E.O. No. 228 — Not tenanted retained area',
            'ra6657_not_covered_tenanted_retained_area' => 'Not covered by R.A. No. 6657, as amended by R.A. No. 9700 — Tenanted retained area',
            'ra6657_not_covered_not_tenanted_retained_area' => 'Not covered by R.A. No. 6657, as amended by R.A. No. 9700 — Not tenanted retained area',
            'ra6657_not_covered_personally_tilled' => 'Not covered by R.A. No. 6657 — Personally tilled by the landowner',
            'ra6657_not_covered_above_18_slope' => 'Not covered by R.A. No. 6657 — Un-acquired portion above 18% slope',
            'pd27_covered_cf_under_process' => 'Covered by P.D. No. 27/E.O. No. 228 — CF under process',
            'pd27_covered_dnyd' => 'Covered by P.D. No. 27/E.O. No. 228 — Distributed but not yet documented (DNYD)',
            'pd27_covered_dnyp' => 'Covered by P.D. No. 27/E.O. No. 228 — Distributed but not yet paid (DNYP)',
            'pd27_covered_under_protest' => 'Covered by P.D. No. 27/E.O. No. 228 — Under protest',
            'ra6657_covered_cf_under_process' => 'Covered by R.A. No. 6657 — CF under process',
            'ra6657_covered_dnyd' => 'Covered by R.A. No. 6657 — Distributed but not yet documented (DNYD)',
            'ra6657_covered_dnyp' => 'Covered by R.A. No. 6657 — Distributed but not yet paid (DNYP)',
            'ra6657_covered_under_protest' => 'Covered by R.A. No. 6657 — Under protest',
        ];
    }

    public static function form4RecommendationOptions(): array
    {
        return [
            'application_complete' => 'The duly accomplished application/request is in order and complete.',
            'requirements_complete_consistent' => 'The mandatory documentary requirements and pertinent documents are complete and consistent in form and substance.',
            'no_pending_case_or_conflict' => 'There is no pending case, protest, or conflict of claims involving the subject land.',
        ];
    }

    public function transferNatureLabel(): string
    {
        return self::transferNatureOptions()[$this->transfer_nature] ?? 'Not specified';
    }

    public function transferorDisplayName(): string
    {
        $names = collect($this->transferors ?? [])->pluck('name')->filter();

        return $names->isNotEmpty() ? $names->implode('; ') : (string) ($this->transferor_name ?? '');
    }

    public function transfereeDisplayName(): string
    {
        $names = collect($this->transferees ?? [])->pluck('name')->filter();

        return $names->isNotEmpty() ? $names->implode('; ') : (string) ($this->transferee_name ?? '');
    }

    public function transferInstrumentDisplay(): string
    {
        $instruments = collect($this->transfer_instruments ?? [])
            ->map(fn ($instrument) => trim((string) ($instrument['name'] ?? $instrument)))
            ->filter();

        return $instruments->isNotEmpty() ? $instruments->implode('; ') : $this->transferNatureLabel();
    }

    /**
     * Return normalized transferor or transferee rows while preserving
     * backward compatibility with the legacy single-link columns.
     */
    public function partyRows(string $party): array
    {
        $isTransferor = $party === 'transferor';
        $jsonField = $isTransferor ? 'transferors' : 'transferees';
        $nameField = $isTransferor ? 'transferor_name' : 'transferee_name';
        $legacyLinkField = $isTransferor ? 'transferor_landowner_id' : 'transferee_landowner_id';

        $rows = collect($this->{$jsonField} ?? [])
            ->map(function ($row) {
                $name = trim((string) data_get($row, 'name', ''));

                if ($name === '') {
                    return null;
                }

                $parcelShares = collect((array) data_get($row, 'parcel_shares', []))
                    ->mapWithKeys(function ($value, $key) {
                        if ($value === null || $value === '') {
                            return [];
                        }

                        return [(string) $key => round((float) $value, 4)];
                    })
                    ->all();

                return [
                    'name' => $name,
                    'landowner_id' => filled(data_get($row, 'landowner_id'))
                        ? (int) data_get($row, 'landowner_id')
                        : null,
                    'parcel_shares' => $parcelShares,
                ];
            })
            ->filter()
            ->values();

        if ($rows->isEmpty() && filled($this->{$nameField})) {
            $legacyNames = preg_split('/\s*;\s*/', trim((string) $this->{$nameField})) ?: [];

            $rows = collect($legacyNames)
                ->filter(fn ($name) => filled($name))
                ->values()
                ->map(function ($name, $index) use ($legacyLinkField) {
                    return [
                        'name' => trim((string) $name),
                        'landowner_id' => $index === 0 && filled($this->{$legacyLinkField})
                            ? (int) $this->{$legacyLinkField}
                            : null,
                        'parcel_shares' => [],
                    ];
                });
        }

        return $rows->values()->all();
    }

    public function linkedLandownerIds(?string $party = null): Collection
    {
        $parties = $party ? [$party] : ['transferor', 'transferee'];

        return collect($parties)
            ->flatMap(fn ($partyType) => collect($this->partyRows($partyType))->pluck('landowner_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    public function allPartiesLinked(): bool
    {
        foreach (['transferor', 'transferee'] as $party) {
            $rows = $this->partyRows($party);

            if (empty($rows) || collect($rows)->contains(fn ($row) => blank($row['landowner_id'] ?? null))) {
                return false;
            }
        }

        return true;
    }

    public function isLinkedToLandowner(int $landownerId): bool
    {
        return $this->linkedLandownerIds()->contains($landownerId);
    }

    public function partyAreaForParcel(string $party, int $landownerId, int $applicationParcelId, float $fallbackArea = 0.0): float
    {
        $rows = collect($this->partyRows($party));
        $row = $rows->first(fn ($item) => (int) ($item['landowner_id'] ?? 0) === $landownerId);

        if (! $row) {
            return 0.0;
        }

        $share = data_get($row, 'parcel_shares.' . $applicationParcelId);

        if ($share !== null && $share !== '') {
            return round((float) $share, 4);
        }

        $linkedCount = max(1, $rows->filter(fn ($item) => filled($item['landowner_id'] ?? null))->count());

        return round($fallbackArea / $linkedCount, 4);
    }

    public function scopeLinkedToLandownerIds(Builder $query, iterable $landownerIds): Builder
    {
        $ids = collect($landownerIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $linkedQuery) use ($ids) {
            $linkedQuery
                ->whereIn('transferor_landowner_id', $ids)
                ->orWhereIn('transferee_landowner_id', $ids);

            foreach ($ids as $id) {
                $linkedQuery
                    ->orWhereJsonContains('transferors', [['landowner_id' => $id]])
                    ->orWhereJsonContains('transferees', [['landowner_id' => $id]]);
            }
        });
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
            self::STATUS_PENDING_LEGAL_REVIEW => self::STATUS_AWAITING_PAYMENT,
            self::STATUS_RETURNED_FOR_COMPLIANCE => self::STATUS_PENDING_LEGAL_REVIEW,
            self::STATUS_AWAITING_PAYMENT => self::STATUS_ENDORSED_LTI,
            self::STATUS_ENDORSED_LTI => self::STATUS_RETURNED_TO_LEGAL,
            self::STATUS_RETURNED_TO_LEGAL => self::STATUS_LEGAL_EVALUATION,
            self::STATUS_LEGAL_EVALUATION => self::STATUS_ENDORSED_CHIEF_LEGAL,
            self::STATUS_ENDORSED_CHIEF_LEGAL => self::STATUS_ENDORSED_PARPO,
            self::STATUS_ENDORSED_PARPO => self::STATUS_FOR_RELEASING,
        ];
    }

    public static function workflowActionLabels(): array
    {
        return [
            self::STATUS_PENDING_LEGAL_REVIEW => 'Record Completeness Review and Payment Order',
            self::STATUS_RETURNED_FOR_COMPLIANCE => 'Record Compliance and Resume Legal Review',
            self::STATUS_AWAITING_PAYMENT => 'Record Payment and Forward to LTID',
            self::STATUS_ENDORSED_LTI => 'Record Return from LTID',
            self::STATUS_RETURNED_TO_LEGAL => 'Begin Legal Evaluation',
            self::STATUS_LEGAL_EVALUATION => 'Record CSW and Forward to Chief Legal',
            self::STATUS_ENDORSED_CHIEF_LEGAL => 'Record Chief Legal Review and Forward to PARPO II',
            self::STATUS_ENDORSED_PARPO => 'Record Return from PARPO II for Final Decision Entry',
            self::STATUS_FOR_RELEASING => 'Record PARPO II Final Decision',
            self::STATUS_APPROVED => 'Manage Release Tracking',
            self::STATUS_NOT_APPROVED => 'Manage Release Tracking',
        ];
    }

    public function workflowActionLabel(): string
    {
        return self::workflowActionLabels()[$this->status] ?? 'Manage Workflow';
    }

    public static function workflowAuthorityLabels(): array
    {
        return [
            self::STATUS_PENDING_LEGAL_REVIEW => 'Legal Division',
            self::STATUS_RETURNED_FOR_COMPLIANCE => 'Legal Division',
            self::STATUS_AWAITING_PAYMENT => 'DAR Cashier / Legal Division',
            self::STATUS_ENDORSED_LTI => 'LTID',
            self::STATUS_RETURNED_TO_LEGAL => 'Legal Division',
            self::STATUS_LEGAL_EVALUATION => 'Legal Division',
            self::STATUS_ENDORSED_CHIEF_LEGAL => 'Chief Legal',
            self::STATUS_ENDORSED_PARPO => 'PARPO II',
            self::STATUS_FOR_RELEASING => self::FINAL_DECISION_AUTHORITY,
        ];
    }

    public function workflowAuthorityLabel(): string
    {
        if ($this->isFinalized()) {
            return $this->decision_authority ?: self::FINAL_DECISION_AUTHORITY;
        }

        return self::workflowAuthorityLabels()[$this->status] ?? 'Legal Division';
    }

    public function nextWorkflowStatus(): ?string
    {
        return self::workflowTransitions()[$this->status] ?? null;
    }


    public function decisionRecordedBy()
    {
        return $this->belongsTo(User::class, 'decision_recorded_by');
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

    public function cswPreparer()
    {
        return $this->belongsTo(User::class, 'csw_prepared_by');
    }

    public function releasedBy()
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}
