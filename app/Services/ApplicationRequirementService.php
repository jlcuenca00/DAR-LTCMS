<?php

namespace App\Services;

use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ApplicationRequirementService
{
    private ?Collection $requirementCatalog = null;

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
        $requirements = $this->requirements();
        $referenceDate = $application->date_of_application ?: now();

        /*
         * The parcel link is an intake prerequisite for the documentary engine:
         * without the subject parcel, the system cannot safely determine whether
         * titled-land or untitled-land evidence applies. This is a data-integrity
         * gate only; linking a parcel never changes ownership.
         */
        $structuralErrors = [];
        if ($application->applicationParcels->isEmpty()) {
            $structuralErrors['parcel'] = 'Link at least one subject Parcel record before completing the documentary intake review.';
        }

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
        $documentErrors = $this->messagesFor($incomplete);
        $errors = array_merge($structuralErrors, $documentErrors);

        return [
            'reference_date' => $referenceDate->toDateString(),
            'requirements' => $rows->all(),
            'blocking_count' => $blockingRows->count(),
            'complete_blocking_count' => $blockingRows->where('complete', true)->count(),
            'incomplete_count' => $incomplete->count() + count($structuralErrors),
            'complete' => $errors === [],
            'errors' => $errors,
            'has_subject_parcel' => $application->applicationParcels->isNotEmpty(),
            'scope_note' => 'Document completeness, age, and parcel-link checks are assistive administrative validations only and are not final legal determinations or ownership-transfer actions.',
        ];
    }

    public function applicableRequirements(LandTransferApplication $application): Collection
    {
        $application->loadMissing('applicationParcels.parcel');

        return $this->requirements()
            ->filter(fn (RequiredDocument $requirement) => $requirement->appliesToApplication($application))
            ->values();
    }

    /**
     * Keep one requirement catalog in memory for the current request. Dashboard
     * attention counts may evaluate many active applications, so re-querying the
     * same catalog for every row would create avoidable N+1 work.
     */
    private function requirements(): Collection
    {
        return $this->requirementCatalog ??= RequiredDocument::query()
            ->orderBy('applies_to')
            ->orderBy('id')
            ->get();
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
            $issuedAt = Carbon::parse((string) $issued)->startOfDay();
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
