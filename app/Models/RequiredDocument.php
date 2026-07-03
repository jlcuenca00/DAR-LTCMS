<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RequiredDocument extends Model
{
    public const CLASSIFICATION_MANDATORY = 'mandatory';
    public const CLASSIFICATION_CASE_DEPENDENT = 'case_dependent';
    public const CLASSIFICATION_REFERENCE = 'reference';

    protected $guarded = [];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'blocks_acceptance' => 'boolean',
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

            if ($document->requirement_classification === self::CLASSIFICATION_CASE_DEPENDENT) {
                $document->is_mandatory = false;
                $document->blocks_acceptance = false;
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



    public static function normalizedReviewName(string $name): string
    {
        $normalized = preg_replace('/\s*\(if available\)\s*/i', '', $name) ?? $name;
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

                $preferred = $group->firstWhere('name', 'Recent Tax Declaration (if available)')
                    ?? $group->first();

                return (int) $document->id === (int) $preferred->id;
            })
            ->values();
    }

    public function classificationLabel(): string
    {
        return match ($this->requirement_classification) {
            self::CLASSIFICATION_MANDATORY => 'Required before acceptance/release',
            self::CLASSIFICATION_CASE_DEPENDENT => 'Case-dependent',
            self::CLASSIFICATION_REFERENCE => 'Reference only',
            default => $this->is_mandatory ? 'Required before acceptance/release' : 'Case-dependent',
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
