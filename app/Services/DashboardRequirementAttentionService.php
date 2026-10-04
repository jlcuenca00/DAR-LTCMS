<?php

namespace App\Services;

use App\Models\LandTransferApplication;

class DashboardRequirementAttentionService
{
    private const BATCH_SIZE = 100;
    private const PREVIEW_SIZE = 12;

    public function __construct(private ApplicationRequirementService $requirements)
    {
    }

    /**
     * Preserve the intake evaluator's conditional, freshness, and parcel rules.
     * Exact counts require visiting active records, but retained IDs and relation
     * payloads stay bounded instead of growing with the whole application table.
     */
    public function summarize(array $activeStatuses): array
    {
        $counts = ['missing_requirements' => 0, 'requirements_complete' => 0];
        $previews = ['missing_requirements' => [], 'requirements_complete' => []];

        LandTransferApplication::query()
            ->select([
                'id', 'status', 'updated_at', 'date_of_application', 'municipality',
                'applicant_type', 'authorized_representative_name',
                'has_special_power_of_attorney', 'applicant_is_juridical_entity',
            ])
            ->whereIn('status', $activeStatuses)
            ->with([
                'documents' => fn ($query) => $query->select([
                    'id', 'land_transfer_application_id', 'required_document_id',
                    'file_path', 'annex_reference', 'source_record_id',
                    'source_record_package_id', 'document_metadata',
                ]),
                'documents.requiredDocument',
                'applicationParcels' => fn ($query) => $query->select([
                    'id', 'land_transfer_application_id', 'parcel_id',
                    'area_hectares', 'title_type', 'title_no',
                ]),
                'applicationParcels.parcel' => fn ($query) => $query->select([
                    'id', 'status', 'area_hectares', 'title_type', 'title_no',
                ]),
            ])
            ->chunkById(self::BATCH_SIZE, function ($applications) use (&$counts, &$previews) {
                foreach ($applications as $application) {
                    $key = $this->requirements->evaluate($application)['complete']
                        ? 'requirements_complete' : 'missing_requirements';
                    $counts[$key]++;
                    $previews[$key][] = [
                        'id' => (int) $application->id,
                        'updated_at' => $application->updated_at?->getTimestamp() ?? PHP_INT_MAX,
                    ];
                }

                foreach ($previews as &$preview) {
                    usort($preview, fn ($left, $right) =>
                        ($left['updated_at'] <=> $right['updated_at']) ?: ($left['id'] <=> $right['id']));
                    $preview = array_slice($preview, 0, self::PREVIEW_SIZE);
                }
                unset($preview);
            });

        return [
            'counts' => $counts,
            'preview_ids' => array_map(fn ($preview) => array_column($preview, 'id'), $previews),
        ];
    }
}
