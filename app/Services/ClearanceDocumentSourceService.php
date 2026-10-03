<?php

namespace App\Services;

use App\Models\ApplicationDocument;
use App\Models\LandTransferApplication;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ClearanceDocumentSourceService
{
    public function candidates(LandTransferApplication $application): array
    {
        $application->loadMissing('documents.requiredDocument');
        $documents = $application->documents->sortBy('id')->values();

        return [
            'title' => $documents->filter(function (ApplicationDocument $document) {
                $name = mb_strtolower((string) $document->requiredDocument?->name);
                return filled(data_get($document->document_metadata, 'title_owner_names'))
                    || filled(data_get($document->document_metadata, 'title_number'))
                    || str_contains($name, 'oct/tct')
                    || str_contains($name, 'tax declaration');
            })->values(),
            'transfer' => $documents->filter(function (ApplicationDocument $document) {
                $name = mb_strtolower((string) $document->requiredDocument?->name);
                return filled(data_get($document->document_metadata, 'transfer_document_title'))
                    || str_contains($name, 'deed')
                    || str_contains($name, 'document to be registered')
                    || str_contains($name, 'transfer instrument')
                    || str_contains($name, 'conveyance');
            })->values(),
        ];
    }

    public function resolve(LandTransferApplication $application): array
    {
        $candidates = $this->candidates($application);
        return [
            'title' => $this->select($candidates['title'], $application->ltc_title_document_id, 'ltc_title_document_id'),
            'transfer' => $this->select($candidates['transfer'], $application->ltc_transfer_document_id, 'ltc_transfer_document_id'),
        ];
    }

    public function assertChronology(LandTransferApplication $application, string $decisionDate): void
    {
        $source = $this->resolve($application)['transfer'];
        $metadata = (array) $source?->document_metadata;
        $issued = $metadata['notarization_date'] ?? $metadata['date_issued'] ?? null;
        if (blank($issued)) {
            return;
        }

        try {
            $documentDate = Carbon::parse((string) $issued)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages(['decision_date' => 'The selected transfer document has an invalid date. Correct the evidence before recording the decision.']);
        }
        if ($documentDate->gt(Carbon::parse($decisionDate)->startOfDay())) {
            throw ValidationException::withMessages(['decision_date' => 'The decision date cannot precede the selected transfer document date.']);
        }
    }

    private function select(Collection $candidates, $selectedId, string $field): ?ApplicationDocument
    {
        if ($selectedId !== null) {
            $document = $candidates->firstWhere('id', (int) $selectedId);
            if (! $document) {
                throw ValidationException::withMessages([$field => 'Select an eligible document from this application for the clearance output.']);
            }
            return $document;
        }
        if ($candidates->count() > 1) {
            throw ValidationException::withMessages([$field => 'More than one eligible source document is recorded. Select the reviewed source in Form No. 4 before generating the clearance.']);
        }
        return $candidates->first();
    }
}
