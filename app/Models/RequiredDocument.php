<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RequiredDocument extends Model
{
    public const CLASSIFICATION_MANDATORY = 'mandatory';
    public const CLASSIFICATION_CASE_DEPENDENT = 'case_dependent';
    public const CLASSIFICATION_REFERENCE = 'reference';

    public const CONDITION_TITLED_LAND = 'titled_land';
    public const CONDITION_UNTITLED_LAND = 'untitled_land';
    public const CONDITION_MUNICIPAL_JURISDICTION = 'municipal_jurisdiction';
    public const CONDITION_CITY_JURISDICTION = 'city_jurisdiction';
    public const CONDITION_AUTHORIZED_REPRESENTATIVE = 'authorized_representative';
    public const CONDITION_JURIDICAL_ENTITY = 'juridical_entity';

    protected $guarded = [];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'blocks_acceptance' => 'boolean',
        'max_age_months' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (RequiredDocument $document): void {
            if (! $document->requirement_classification) {
                $document->requirement_classification = $document->is_mandatory
                    ? self::CLASSIFICATION_MANDATORY
                    : self::CLASSIFICATION_CASE_DEPENDENT;
            }

            if ($document->blocks_acceptance === null) {
                $document->blocks_acceptance = (bool) $document->is_mandatory;
            }

            if ($document->requirement_classification === self::CLASSIFICATION_REFERENCE) {
                $document->is_mandatory = false;
                $document->blocks_acceptance = false;
            }

            // Case-dependent items may still block intake when their condition is
            // true. Keep is_mandatory false so the UI can distinguish them from
            // always-required documents, but do not force blocks_acceptance off.
            if ($document->requirement_classification === self::CLASSIFICATION_CASE_DEPENDENT) {
                $document->is_mandatory = false;
            }
        });
    }

    public function scopeAcceptanceBlocking(Builder $query): Builder
    {
        return $query->where('blocks_acceptance', true);
    }

    public function blocksAcceptance(): bool
    {
        return (bool) $this->blocks_acceptance;
    }

    public function isCaseDependent(): bool
    {
        return $this->requirement_classification === self::CLASSIFICATION_CASE_DEPENDENT;
    }

    public function isReferenceOnly(): bool
    {
        return $this->requirement_classification === self::CLASSIFICATION_REFERENCE;
    }

    public function appliesToApplication(LandTransferApplication $application): bool
    {
        $condition = $this->condition_key;

        if (blank($condition)) {
            return true;
        }

        $application->loadMissing('applicationParcels.parcel');
        $parcels = $application->applicationParcels;

        $hasUntitledLand = $parcels->contains(function ($applicationParcel) {
            $titleType = strtolower((string) ($applicationParcel->title_type ?? $applicationParcel->parcel?->title_type));
            $titleNumber = trim((string) ($applicationParcel->title_no ?? $applicationParcel->parcel?->title_no));

            return $titleType === 'untitled' || $titleNumber === '';
        });

        $hasTitledLand = $parcels->contains(function ($applicationParcel) {
            $titleType = strtolower((string) ($applicationParcel->title_type ?? $applicationParcel->parcel?->title_type));
            $titleNumber = trim((string) ($applicationParcel->title_no ?? $applicationParcel->parcel?->title_no));

            return $titleType !== 'untitled' && $titleNumber !== '';
        });

        $municipality = trim((string) $application->municipality);
        $isCity = $municipality !== '' && str_contains(mb_strtolower($municipality), 'city');

        return match ($condition) {
            self::CONDITION_TITLED_LAND => $hasTitledLand,
            self::CONDITION_UNTITLED_LAND => $hasUntitledLand,
            self::CONDITION_CITY_JURISDICTION => $isCity,
            self::CONDITION_MUNICIPAL_JURISDICTION => $municipality !== '' && ! $isCity,
            self::CONDITION_AUTHORIZED_REPRESENTATIVE =>
                $application->applicant_type === 'authorized_representative'
                || filled($application->authorized_representative_name)
                || (bool) $application->has_special_power_of_attorney,
            self::CONDITION_JURIDICAL_ENTITY => (bool) $application->applicant_is_juridical_entity,
            default => true,
        };
    }

    public function blocksApplication(LandTransferApplication $application): bool
    {
        return $this->blocksAcceptance() && $this->appliesToApplication($application);
    }

    public static function normalizedReviewName(string $name): string
    {
        $normalized = preg_replace('/\s*\((?:if available|if applicable|when applicable|where applicable)[^)]*\)\s*/i', '', $name) ?? $name;
        $normalized = preg_replace('/\s+/', ' ', trim($normalized)) ?? trim($normalized);

        return mb_strtolower($normalized);
    }

    public static function deduplicateForApplicationReview($requirements)
    {
        $requirements = collect($requirements)->values();
        $grouped = $requirements->groupBy(
            fn (RequiredDocument $document) => self::normalizedReviewName((string) $document->name)
        );

        return $requirements
            ->filter(function (RequiredDocument $document) use ($grouped): bool {
                $group = $grouped->get(self::normalizedReviewName((string) $document->name), collect());

                $preferred = $group->firstWhere('name', 'Certified True Copy of Current Tax Declaration (Untitled Land)')
                    ?? $group->first();

                return (int) $document->id === (int) $preferred->id;
            })
            ->values();
    }

    public function classificationLabel(): string
    {
        return match ($this->requirement_classification) {
            self::CLASSIFICATION_MANDATORY => 'Required before acceptance',
            self::CLASSIFICATION_CASE_DEPENDENT => $this->blocks_acceptance ? 'Required when applicable' : 'Case-dependent',
            self::CLASSIFICATION_REFERENCE => 'Reference only',
            default => $this->is_mandatory ? 'Required before acceptance' : 'Case-dependent',
        };
    }

    public function classificationBadgeClass(): string
    {
        return match ($this->requirement_classification) {
            self::CLASSIFICATION_MANDATORY => 'staff-badge-red',
            self::CLASSIFICATION_CASE_DEPENDENT => 'staff-badge-amber',
            self::CLASSIFICATION_REFERENCE => 'staff-badge-slate',
            default => $this->is_mandatory ? 'staff-badge-red' : 'staff-badge-amber',
        };
    }
}
