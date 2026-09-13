<?php

namespace App\Services;

use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ApplicationRequirementService
{
    /**
     * Evaluate the Citizen's Charter documentary requirements that apply to one
     * application. This is an administrative completeness aid only; authorized
     * DAR personnel remain responsible for legal acceptance and evaluation.
     */
    public function evaluate(LandTransferApplication $application): array
    {
        $application->loadMissing([
            'documents.requiredDocument',
            'applicationParcels.parcel',
        ]);

        $uploaded = $application->documents->keyBy('required_document_id');
        $requirements = RequiredDocument::query()
            ->orderBy('applies_to')
            ->orderBy('id')
            ->get();

        $referenceDate = $application->date_of_application ?: now();

        $rows = $requirements->map(function (RequiredDocument $requirement) use ($application, $uploaded, $referenceDate) {
            $applicable = $requirement->appliesToApplication($application);
            $blocking = $requirement->blocksApplication($application);
            $document = $uploaded->get($requirement->id);
            $present = $document !== null;

            $freshness = $this->freshnessState($requirement, $document?->document_metadata ?? [], $referenceDate);
            $complete = ! $blocking || ($present && $freshness['valid']);

            return [
                'id' => (int) $requirement->id,
                'name' => $requirement->name,
                'party' => $requirement->applies_to,
                'classification' => $requirement->requirement_classification,
                'condition_key' => $requirement->condition_key,
                'applicable' => $applicable,
                'blocking' => $blocking,
                'present' => $present,
                'max_age_months' => $requirement->max_age_months,
                'freshness_valid' => $freshness['valid'],
                'freshness_message' => $freshness['message'],
                'complete' => $complete,
            ];
        })->values();

        $blockingRows = $rows->where('blocking', true)->values();
        $incomplete = $blockingRows->where('complete', false)->values();

        return [
            'reference_date' => $referenceDate->toDateString(),
            'requirements' => $rows->all(),
            'blocking_count' => $blockingRows->count(),
            'complete_blocking_count' => $blockingRows->where('complete', true)->count(),
            'incomplete_count' => $incomplete->count(),
            'complete' => $incomplete->isEmpty(),
            'errors' => $this->messagesFor($incomplete),
            'scope_note' => 'Document completeness and age checks are assistive administrative validations only and are not final legal determinations.',
        ];
    }

    public function applicableRequirements(LandTransferApplication $application): Collection
    {
        return RequiredDocument::query()
            ->orderBy('applies_to')
            ->orderBy('id')
            ->get()
            ->filter(fn (RequiredDocument $requirement) => $requirement->appliesToApplication($application))
            ->values();
    }

    private function freshnessState(RequiredDocument $requirement, array $metadata, CarbonInterface $referenceDate): array
    {
        $months = (int) ($requirement->max_age_months ?? 0);

        if ($months <= 0) {
            return ['valid' => true, 'message' => null];
        }

        $issued = data_get($metadata, 'date_issued');

        if (blank($issued)) {
            return [
                'valid' => false,
                'message' => "Encode the document's date issued so the {$months}-month validity can be checked.",
            ];
        }

        try {
            $issuedAt = now()->parse((string) $issued)->startOfDay();
            $applicationDate = $referenceDate->copy()->startOfDay();
            $earliestValidDate = $applicationDate->copy()->subMonthsNoOverflow($months);
        } catch (\Throwable) {
            return ['valid' => false, 'message' => 'The encoded date issued is invalid.'];
        }

        if ($issuedAt->gt($applicationDate)) {
            return ['valid' => false, 'message' => 'The date issued cannot be later than the application date.'];
        }

        if ($issuedAt->lt($earliestValidDate)) {
            return [
                'valid' => false,
                'message' => "The document is older than {$months} months as of the application date.",
            ];
        }

        return ['valid' => true, 'message' => null];
    }

    private function messagesFor(Collection $incomplete): array
    {
        $errors = [];

        foreach ($incomplete as $row) {
            $prefix = 'requirement_' . $row['id'];

            if (! $row['present']) {
                $errors[$prefix] = 'Missing applicable requirement: ' . $row['name'] . '.';
                continue;
            }

            if (! $row['freshness_valid']) {
                $errors[$prefix] = $row['name'] . ': ' . ($row['freshness_message'] ?: 'Document validity could not be confirmed.');
            }
        }

        return $errors;
    }
}
