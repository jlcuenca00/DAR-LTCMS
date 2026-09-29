<?php

namespace App\Services;

use App\Models\Landowner;
use App\Models\LandTransferApplication;
use Illuminate\Validation\ValidationException;

class ApplicationPartyIntegrityService
{
    /**
     * Keep current JSON party rows and their legacy compatibility columns aligned
     * whenever party JSON is newly encoded or deliberately edited.
     */
    public function synchronizeCompatibilityFields(LandTransferApplication $application): void
    {
        foreach ($this->partyFields() as [$jsonField, $nameField, $linkField]) {
            if ($application->exists && ! $application->isDirty($jsonField)) {
                continue;
            }

            $rows = collect((array) $application->getAttribute($jsonField))
                ->filter(fn ($row) => is_array($row))
                ->values();

            if ($rows->isEmpty()) {
                continue;
            }

            $names = $rows
                ->map(fn ($row) => trim((string) data_get($row, 'name', '')))
                ->filter();

            $primaryLandownerId = $rows
                ->pluck('landowner_id')
                ->first(fn ($id) => filled($id));

            $application->setAttribute($nameField, $names->implode('; '));
            $application->setAttribute(
                $linkField,
                filled($primaryLandownerId) ? (int) $primaryLandownerId : null
            );
        }
    }

    public function inspect(LandTransferApplication $application): array
    {
        $issues = [];

        foreach ($this->partyFields() as [$jsonField, $nameField, $linkField, $label]) {
            $rows = collect((array) $application->getAttribute($jsonField))
                ->filter(fn ($row) => is_array($row))
                ->values();

            // Empty JSON is retained as valid legacy compatibility. In that case,
            // partyRows() falls back to the historical name/FK columns.
            if ($rows->isEmpty()) {
                continue;
            }

            $names = $rows
                ->map(fn ($row) => trim((string) data_get($row, 'name', '')))
                ->values();

            if ($names->contains(fn ($name) => $name === '')) {
                $issues[] = ucfirst($label).' party rows contain a blank name.';
            }

            $ids = $rows
                ->pluck('landowner_id')
                ->filter(fn ($id) => filled($id))
                ->map(fn ($id) => (int) $id)
                ->values();

            if ($ids->count() !== $ids->unique()->count()) {
                $issues[] = ucfirst($label).' party rows contain the same Landowner record more than once.';
            }

            if ($ids->isNotEmpty()) {
                $existingIds = Landowner::query()
                    ->whereIn('id', $ids->unique()->all())
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id);

                $missingIds = $ids->unique()->diff($existingIds)->values();

                if ($missingIds->isNotEmpty()) {
                    $issues[] = ucfirst($label).' party rows reference missing Landowner ID(s): '.$missingIds->implode(', ').'.';
                }
            }

            $expectedSummary = $names->filter()->implode('; ');
            $actualSummary = trim((string) $application->getAttribute($nameField));

            if ($actualSummary !== $expectedSummary) {
                $issues[] = ucfirst($label).' party-name summary does not match the JSON party rows.';
            }

            $expectedPrimary = $ids->first();
            $actualPrimary = $application->getAttribute($linkField);
            $actualPrimary = filled($actualPrimary) ? (int) $actualPrimary : null;

            if ($actualPrimary !== $expectedPrimary) {
                $issues[] = ucfirst($label).' primary Landowner compatibility link does not match the first linked JSON party row.';
            }
        }

        return [
            'valid' => empty($issues),
            'issues' => array_values(array_unique($issues)),
        ];
    }

    public function assertValid(LandTransferApplication $application): void
    {
        $inspection = $this->inspect($application);

        if (! $inspection['valid']) {
            throw ValidationException::withMessages([
                'parties' => $inspection['issues'],
            ]);
        }
    }

    private function partyFields(): array
    {
        return [
            ['transferors', 'transferor_name', 'transferor_landowner_id', 'transferor'],
            ['transferees', 'transferee_name', 'transferee_landowner_id', 'transferee'],
        ];
    }
}
