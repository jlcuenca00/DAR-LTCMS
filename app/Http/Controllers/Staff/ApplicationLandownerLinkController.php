<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Landholding;
use App\Models\LandTransferApplication;
use App\Models\Landowner;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApplicationLandownerLinkController extends Controller
{
    public function update(Request $request, LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return redirect()
                ->to(route('staff.applications.show', $application) . '#landowner-links')
                ->with('error', 'Finalized applications are locked. Landowner record links can no longer be changed.');
        }

        $existingTransferors = $application->partyRows('transferor');
        $existingTransferees = $application->partyRows('transferee');

        $validated = $request->validate([
            'transferors' => ['required', 'array', 'size:' . count($existingTransferors)],
            'transferors.*.name' => ['required', 'string', 'max:255'],
            'transferors.*.landowner_id' => ['nullable', 'distinct', 'exists:landowners,id'],
            'transferors.*.parcel_shares' => ['nullable', 'array'],
            'transferors.*.parcel_shares.*' => ['nullable', 'numeric', 'min:0', 'max:999999.9999'],
            'transferees' => ['required', 'array', 'size:' . count($existingTransferees)],
            'transferees.*.name' => ['required', 'string', 'max:255'],
            'transferees.*.landowner_id' => ['nullable', 'distinct', 'exists:landowners,id'],
            'transferees.*.parcel_shares' => ['nullable', 'array'],
            'transferees.*.parcel_shares.*' => ['nullable', 'numeric', 'min:0', 'max:999999.9999'],
            'split_equally' => ['nullable', 'boolean'],
            'sync_current_landholdings' => ['nullable', 'boolean'],
        ]);

        $application->loadMissing('applicationParcels.parcel');

        $transferors = $this->normalizeRows($validated['transferors'], $existingTransferors);
        $transferees = $this->normalizeRows($validated['transferees'], $existingTransferees);

        if ($request->boolean('split_equally')) {
            if ($application->applicationParcels->isEmpty()) {
                return back()->withErrors([
                    'split_equally' => 'Link at least one parcel before splitting hectare shares.',
                ])->withInput();
            }

            $hasUnlinkedParty = collect(array_merge($transferors, $transferees))
                ->contains(fn ($row) => blank($row['landowner_id'] ?? null));

            if ($hasUnlinkedParty) {
                return back()->withErrors([
                    'split_equally' => 'Link every transferor and transferee before splitting parcel areas equally.',
                ])->withInput();
            }

            $transferors = $this->applyEqualShares($transferors, $application, 'transferor');
            $transferees = $this->applyEqualShares($transferees, $application, 'transferee');
        }

        $syncLandholdings = $request->boolean('sync_current_landholdings');

        if ($syncLandholdings) {
            $shareErrors = $this->validateCurrentLandholdingShares($transferors, $application);

            if (! empty($shareErrors)) {
                return back()->withErrors($shareErrors)->withInput();
            }
        }

        $oldLinks = [
            'transferors' => $application->partyRows('transferor'),
            'transferees' => $application->partyRows('transferee'),
        ];

        DB::transaction(function () use ($application, $transferors, $transferees, $syncLandholdings, $oldLinks) {
            $this->savePartyRows($application, $transferors, $transferees);

            $syncedLandholdings = $syncLandholdings
                ? $this->syncCurrentTransferorLandholdings($application, $transferors)
                : [];

            AuditLogger::record(
                'application_landowner_links_updated',
                $application,
                $application,
                [
                    'old_links' => $oldLinks,
                    'new_links' => [
                        'transferors' => $transferors,
                        'transferees' => $transferees,
                    ],
                    'current_landholdings_synced' => $syncLandholdings,
                    'synced_landholding_ids' => $syncedLandholdings,
                    'scope_note' => 'Application parties were linked for clearance processing, validation, and traceability. Any current landholding shares were saved only through the explicit staff-selected synchronization action. No approval, ownership transfer, or registry mutation was performed.',
                ]
            );
        });

        $message = $syncLandholdings
            ? 'Party links and current co-owner hectare shares were saved successfully.'
            : 'Landowner record links saved successfully.';

        return redirect()
            ->to(route('staff.applications.show', $application) . '#landowner-links')
            ->with('success', $message);
    }

    public function createFromApplicationParty(Request $request, LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return redirect()
                ->to(route('staff.applications.show', $application) . '#landowner-links')
                ->with('error', 'Finalized applications are locked. New landowner records can no longer be linked from this application.');
        }

        $validated = $request->validate([
            'party' => ['required', Rule::in(['transferor', 'transferee'])],
            'index' => ['nullable', 'integer', 'min:0'],
        ]);

        $party = $validated['party'];
        $index = (int) ($validated['index'] ?? 0);
        $rows = $application->partyRows($party);
        $displayParty = $party === 'transferor' ? 'Transferor' : 'Transferee';

        if (! array_key_exists($index, $rows)) {
            return redirect()
                ->to(route('staff.applications.show', $application) . '#landowner-links')
                ->with('error', "The selected {$displayParty} entry no longer exists.");
        }

        if (filled($rows[$index]['landowner_id'] ?? null)) {
            return redirect()
                ->to(route('staff.applications.show', $application) . '#landowner-links')
                ->with('error', "This {$displayParty} is already linked to a landowner record.");
        }

        $sourceName = trim((string) ($rows[$index]['name'] ?? ''));

        if ($sourceName === '') {
            return redirect()
                ->to(route('staff.applications.show', $application) . '#landowner-links')
                ->with('error', "Cannot create the {$displayParty} record because the party name is blank.");
        }

        [$firstName, $middleName, $lastName, $suffix] = $this->splitName($sourceName);

        $landowner = null;

        DB::transaction(function () use ($application, $party, $index, $displayParty, $sourceName, $firstName, $middleName, $lastName, $suffix, &$landowner) {
            $landowner = Landowner::create([
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'suffix' => $suffix,
                'barangay' => $application->barangay,
                'municipality' => $application->municipality,
                'province' => 'Negros Oriental',
            ]);

            $transferors = $application->partyRows('transferor');
            $transferees = $application->partyRows('transferee');
            $targetRows = $party === 'transferor' ? $transferors : $transferees;
            $targetRows[$index]['landowner_id'] = $landowner->id;

            if ($party === 'transferor') {
                $transferors = array_values($targetRows);
            } else {
                $transferees = array_values($targetRows);
            }

            $this->savePartyRows($application, $transferors, $transferees);

            AuditLogger::record(
                'landowner_record_created_from_application_party',
                $application,
                $landowner,
                [
                    'party' => $party,
                    'party_index' => $index,
                    'display_party' => $displayParty,
                    'source_name' => $sourceName,
                    'created_landowner_id' => $landowner->id,
                    'scope_note' => 'One separate landowner/person record was created from the selected application party for processing and traceability only. This does not assign a parcel, create a landholding, transfer ownership, or mutate registry records.',
                ]
            );
        });

        return redirect()
            ->to(route('staff.applications.show', $application) . '#landowner-links')
            ->with('success', "{$displayParty} record for {$sourceName} was created and linked separately.");
    }

    private function normalizeRows(array $rows, array $existingRows): array
    {
        $submittedRows = array_values($rows);

        return collect($existingRows)
            ->values()
            ->map(function ($existingRow, $index) use ($submittedRows) {
                $row = $submittedRows[$index] ?? [];
                $shares = collect((array) ($row['parcel_shares'] ?? []))
                    ->mapWithKeys(function ($value, $key) {
                        if ($value === null || $value === '') {
                            return [];
                        }

                        return [(string) $key => round((float) $value, 4)];
                    })
                    ->all();

                return [
                    // Party names are edited only through the application record,
                    // never through the linking endpoint.
                    'name' => trim((string) ($existingRow['name'] ?? '')),
                    'landowner_id' => filled($row['landowner_id'] ?? null)
                        ? (int) $row['landowner_id']
                        : null,
                    'parcel_shares' => $shares,
                ];
            })
            ->filter(fn ($row) => $row['name'] !== '')
            ->values()
            ->all();
    }

    private function applyEqualShares(array $rows, LandTransferApplication $application, string $party): array
    {
        $linkedRows = collect($rows)->filter(fn ($row) => filled($row['landowner_id'] ?? null));

        if ($linkedRows->count() !== count($rows)) {
            return $rows;
        }

        $ownerCount = max(1, count($rows));

        foreach ($application->applicationParcels as $applicationParcel) {
            $shareableArea = $party === 'transferor'
                ? (float) ($applicationParcel->parcel?->area_hectares ?? $applicationParcel->area_hectares ?? 0)
                : (float) ($applicationParcel->area_hectares ?? $applicationParcel->parcel?->area_hectares ?? 0);
            $baseShare = floor(($shareableArea / $ownerCount) * 10000) / 10000;
            $allocated = 0.0;

            foreach ($rows as $index => &$row) {
                $share = $index === array_key_last($rows)
                    ? round($shareableArea - $allocated, 4)
                    : round($baseShare, 4);

                $row['parcel_shares'][(string) $applicationParcel->id] = $share;
                $allocated += $share;
            }
            unset($row);
        }

        return $rows;
    }

    private function validateCurrentLandholdingShares(array $transferors, LandTransferApplication $application): array
    {
        $errors = [];

        if ($application->applicationParcels->isEmpty()) {
            return ['sync_current_landholdings' => 'Link at least one parcel before synchronizing current landholdings.'];
        }

        foreach ($transferors as $index => $row) {
            if (blank($row['landowner_id'] ?? null)) {
                $errors["transferors.{$index}.landowner_id"] = 'Every transferor must be linked before current landholdings can be synchronized.';
            }
        }

        if (! empty($errors)) {
            return $errors;
        }

        foreach ($application->applicationParcels as $applicationParcel) {
            if (! $applicationParcel->parcel_id || ! $applicationParcel->parcel) {
                $errors['sync_current_landholdings'] = 'Every synchronized landholding must be linked to an existing Parcel Record.';
                continue;
            }

            $parcelArea = round((float) ($applicationParcel->parcel->area_hectares ?? $applicationParcel->area_hectares ?? 0), 4);
            $totalSubmittedShares = 0.0;
            $positiveOwnerIds = [];

            if ($parcelArea <= 0) {
                $errors['sync_current_landholdings'] = 'Each linked Parcel Record must have a valid area before hectare shares can be synchronized.';
                continue;
            }

            foreach ($transferors as $index => $row) {
                $share = round((float) data_get($row, 'parcel_shares.' . $applicationParcel->id, 0), 4);

                if ($share < 0) {
                    $errors["transferors.{$index}.parcel_shares.{$applicationParcel->id}"] = 'Hectare shares cannot be negative.';
                    continue;
                }

                if ($share > $parcelArea + 0.0002) {
                    $errors["transferors.{$index}.parcel_shares.{$applicationParcel->id}"] = 'A co-owner share cannot exceed the Parcel Record area of ' . number_format($parcelArea, 4) . ' hectares.';
                    continue;
                }

                if ($share > 0) {
                    $positiveOwnerIds[] = (int) $row['landowner_id'];
                    $totalSubmittedShares += $share;
                }
            }

            if ($totalSubmittedShares <= 0) {
                $errors["parcel_share_total_{$applicationParcel->id}"] = 'Enter at least one positive current hectare share for ' . ($applicationParcel->parcel_code ?: $applicationParcel->parcel->parcel_code ?: 'the linked parcel') . '.';
                continue;
            }

            $existingOtherShares = (float) Landholding::query()
                ->where('parcel_id', $applicationParcel->parcel_id)
                ->where('status', Landholding::STATUS_ACTIVE)
                ->when(! empty($positiveOwnerIds), fn ($query) => $query->whereNotIn('landowner_id', array_unique($positiveOwnerIds)))
                ->sum('area_hectares');

            $combinedActiveShares = round($existingOtherShares + $totalSubmittedShares, 4);

            if ($combinedActiveShares > $parcelArea + 0.0002) {
                $errors["parcel_share_total_{$applicationParcel->id}"] = 'The submitted co-owner shares plus other active landholdings would exceed the Parcel Record area of ' . number_format($parcelArea, 4) . ' hectares.';
            }
        }

        return $errors;
    }

    private function syncCurrentTransferorLandholdings(LandTransferApplication $application, array $transferors): array
    {
        $syncedIds = [];

        foreach ($application->applicationParcels as $applicationParcel) {
            if (! $applicationParcel->parcel_id) {
                continue;
            }

            foreach ($transferors as $row) {
                $share = (float) data_get($row, 'parcel_shares.' . $applicationParcel->id, 0);

                if (! $row['landowner_id'] || $share <= 0) {
                    continue;
                }

                $landholding = Landholding::updateOrCreate(
                    [
                        'landowner_id' => $row['landowner_id'],
                        'parcel_id' => $applicationParcel->parcel_id,
                    ],
                    [
                        'area_hectares' => round($share, 4),
                        'status' => Landholding::STATUS_ACTIVE,
                        'source_application_id' => $application->id,
                        'remarks' => 'Current co-owner hectare share confirmed by DAR Staff during clearance application review. This is an administrative reference record only.',
                    ]
                );

                $syncedIds[] = $landholding->id;
            }
        }

        return array_values(array_unique($syncedIds));
    }

    private function savePartyRows(LandTransferApplication $application, array $transferors, array $transferees): void
    {
        $application->forceFill([
            'transferors' => array_values($transferors),
            'transferees' => array_values($transferees),
            'transferor_name' => collect($transferors)->pluck('name')->filter()->implode('; '),
            'transferee_name' => collect($transferees)->pluck('name')->filter()->implode('; '),
            'transferor_landowner_id' => collect($transferors)->pluck('landowner_id')->filter()->first(),
            'transferee_landowner_id' => collect($transferees)->pluck('landowner_id')->filter()->first(),
        ])->save();
    }

    private function splitName(string $name): array
    {
        $suffixes = ['Jr.', 'Jr', 'Sr.', 'Sr', 'II', 'III', 'IV', 'V'];
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        if (count($parts) === 0) {
            return ['Unnamed', null, 'Record', null];
        }

        $suffix = null;
        $lastToken = end($parts);

        if ($lastToken !== false && in_array($lastToken, $suffixes, true)) {
            $suffix = array_pop($parts);
        }

        if (count($parts) === 1) {
            return [$parts[0], null, $parts[0], $suffix];
        }

        $firstName = array_shift($parts);
        $lastName = array_pop($parts);
        $middleName = count($parts) > 0 ? implode(' ', $parts) : null;

        return [$firstName, $middleName, $lastName, $suffix];
    }
}
