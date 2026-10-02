<?php

namespace App\Services;

use App\Models\LandTransferApplication;

class ApplicationClearanceIntegrityService
{
    public const AREA_TOLERANCE = 0.0001;

    public function inspect(LandTransferApplication $application): array
    {
        $application->loadMissing('clearance');
        $issues = [];
        $clearance = $application->clearance;
        $isCurrentFinal = in_array($application->status, LandTransferApplication::FINAL_STATUSES, true);

        if ($isCurrentFinal && ! $clearance) {
            $issues[] = 'Current final decision does not have an immutable LTC Form No. 5 clearance snapshot.';

            return ['valid' => false, 'issues' => $issues];
        }

        if (! $application->isFinalized() && $clearance) {
            $issues[] = 'A non-final application already has an immutable clearance snapshot.';
        }

        if (! $clearance) {
            return [
                'valid' => empty($issues),
                'issues' => array_values(array_unique($issues)),
            ];
        }

        if ((string) $clearance->decision_status !== (string) $application->status) {
            $issues[] = 'Clearance decision_status does not match the application final status.';
        }

        if ((string) $clearance->application_code !== (string) $application->application_code) {
            $issues[] = 'Clearance application_code does not match its parent application.';
        }

        $allowedDecisionStatuses = array_merge(
            LandTransferApplication::FINAL_STATUSES,
            LandTransferApplication::LEGACY_FINAL_STATUSES
        );

        if (! in_array((string) $clearance->decision_status, $allowedDecisionStatuses, true)) {
            $issues[] = 'Clearance decision_status is not a recognized final decision state.';
        }

        $hasVersionedFormSnapshot = is_array($clearance->form_snapshot)
            && filled(data_get($clearance->form_snapshot, 'snapshot_version'));

        if ($hasVersionedFormSnapshot && $isCurrentFinal) {
            $decisionComparisons = [
                'decision_authority' => [
                    (string) ($clearance->decision_authority ?? ''),
                    (string) ($application->decision_authority ?? ''),
                ],
                'decision_officer_name' => [
                    (string) ($clearance->decision_officer_name ?? ''),
                    (string) ($application->decision_officer_name ?? ''),
                ],
                'decision_date' => [
                    optional($clearance->decision_date)->toDateString(),
                    optional($application->decision_date)->toDateString(),
                ],
                'decision_recorded_by' => [
                    $clearance->decision_recorded_by !== null ? (int) $clearance->decision_recorded_by : null,
                    $application->decision_recorded_by !== null ? (int) $application->decision_recorded_by : null,
                ],
                'decision_recorded_at' => [
                    optional($clearance->decision_recorded_at)->toDateTimeString(),
                    optional($application->decision_recorded_at)->toDateTimeString(),
                ],
            ];

            foreach ($decisionComparisons as $field => [$snapshotValue, $applicationValue]) {
                if ($snapshotValue === null || $snapshotValue === '') {
                    $issues[] = "Clearance {$field} is missing from the immutable final-decision snapshot.";
                    continue;
                }

                if ((string) $snapshotValue !== (string) $applicationValue) {
                    $issues[] = "Clearance {$field} does not match the frozen application final-decision record.";
                }
            }
        } else {
            // Historical compatibility: pre-versioned snapshots remain readable.
            // When identity fields are present, still detect contradictions.
            foreach ([
                'decision_authority',
                'decision_officer_name',
                'decision_recorded_by',
            ] as $field) {
                $snapshotValue = $clearance->{$field};
                $applicationValue = $application->{$field};

                if (filled($snapshotValue) && filled($applicationValue)
                    && (string) $snapshotValue !== (string) $applicationValue) {
                    $issues[] = "Clearance {$field} does not match the application final-decision record.";
                }
            }

            if ($clearance->decision_date && $application->decision_date
                && $clearance->decision_date->toDateString() !== $application->decision_date->toDateString()) {
                $issues[] = 'Clearance decision_date does not match the application final-decision record.';
            }

            if ($clearance->decision_recorded_at && $application->decision_recorded_at
                && $clearance->decision_recorded_at->toDateTimeString() !== $application->decision_recorded_at->toDateTimeString()) {
                $issues[] = 'Clearance decision_recorded_at does not match the application final-decision record.';
            }
        }

        if ($isCurrentFinal && (int) data_get($clearance->form_snapshot, 'snapshot_version', 0) >= 2) {
            $issues = array_merge($issues, $this->inspectFrozenInputs($application));
        }

        $snapshotArea = round((float) collect((array) $clearance->parcel_snapshot)
            ->sum(fn ($row) => is_array($row) ? (float) ($row['area_hectares'] ?? 0) : 0), 4);
        $recordedArea = round((float) $clearance->total_area_hectares, 4);

        if (abs($recordedArea - $snapshotArea) > self::AREA_TOLERANCE) {
            $issues[] = 'Clearance total_area_hectares does not equal the sum of its immutable parcel_snapshot.';
        }

        return [
            'valid' => empty($issues),
            'issues' => array_values(array_unique($issues)),
        ];
    }

    private function inspectFrozenInputs(LandTransferApplication $application): array
    {
        $clearance = $application->clearance;
        $issues = [];
        $comparisons = [
            'transferor_name' => [$clearance->transferor_name, $application->transferorDisplayName()],
            'transferee_name' => [$clearance->transferee_name, $application->transfereeDisplayName()],
            'municipality' => [$clearance->municipality, $application->municipality],
            'barangay' => [$clearance->barangay, $application->barangay],
            'or_number' => [data_get($clearance->form_snapshot, 'or_number'), $application->or_number],
            'or_date' => [data_get($clearance->form_snapshot, 'or_date'), $application->or_date?->toDateString()],
            'amount_paid' => [
                data_get($clearance->form_snapshot, 'amount_paid'),
                $application->amount_paid !== null ? number_format((float) $application->amount_paid, 2, '.', '') : null,
            ],
        ];

        foreach ($comparisons as $field => [$snapshot, $frozen]) {
            if ((string) $snapshot !== (string) $frozen) {
                $issues[] = "Clearance {$field} does not match the frozen application record.";
            }
        }

        // Compare only frozen application-parcel values. Master parcel records
        // may legitimately change later and must not rewrite historical output.
        $rows = $application->applicationParcels()->orderBy('id')->get()->keyBy('id');
        $snapshots = collect((array) $clearance->parcel_snapshot);
        $ids = $snapshots->map(fn ($row) => is_array($row) ? (int) ($row['application_parcel_id'] ?? 0) : 0);
        if ($ids->contains(0) || $ids->unique()->count() !== $ids->count()
            || $ids->sort()->values()->all() !== $rows->keys()->map(fn ($id) => (int) $id)->sort()->values()->all()) {
            $issues[] = 'Clearance parcel snapshot does not match the frozen application parcel set.';
            return $issues;
        }

        foreach ($snapshots as $snapshot) {
            $row = $rows->get((int) $snapshot['application_parcel_id']);
            foreach (['parcel_id', 'area_hectares', 'area_square_meters', 'parcel_code', 'title_no',
                'tax_decl_no', 'lot_number', 'survey_plan_number', 'title_type', 'rod_office'] as $field) {
                $frozen = $row->{$field};
                // Null descriptive fields were populated from master data when
                // generated. Their stored snapshot remains authoritative.
                if ($frozen === null && ! in_array($field, ['parcel_id', 'area_hectares'], true)) {
                    continue;
                }
                $value = $snapshot[$field] ?? null;
                $matches = in_array($field, ['area_hectares', 'area_square_meters'], true)
                    ? is_numeric($value) && bccomp((string) $value, (string) ($frozen ?? 0), $field === 'area_hectares' ? 4 : 2) === 0
                    : (string) $value === (string) $frozen;
                if (! $matches) {
                    $issues[] = "Clearance parcel {$row->id} {$field} does not match the frozen application parcel.";
                }
            }
            foreach (['parcel_number' => 'parcel_code', 'title_number' => 'title_no'] as $alias => $field) {
                if ((string) ($snapshot[$alias] ?? '') !== (string) ($snapshot[$field] ?? '')) {
                    $issues[] = "Clearance parcel {$row->id} {$alias} contradicts its snapshot {$field}.";
                }
            }
        }

        return $issues;
    }
}
