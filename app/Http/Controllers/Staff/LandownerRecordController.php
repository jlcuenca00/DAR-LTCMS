<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LandholdingAreaValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LandownerRecordController extends Controller
{
    public function show(Landowner $landowner, LandholdingAreaValidationService $hectareValidator)
    {
        $landowner->load('user');
        $landholdings = $landowner->landholdings()->with(['parcel', 'sourceApplication'])
            ->orderByDesc('id')->paginate(15, ['*'], 'holdings_page')->withQueryString()->fragment('landholdings');
        $activeHoldingCount = $landowner->activeLandholdings()->count();
        $otherHoldingCount = $landowner->landholdings()->where('status', '!=', 'active')->count();
        $sourcePackages = $landowner->sourceRecordPackages()->orderByDesc('id')
            ->paginate(15, ['*'], 'packages_page')->withQueryString()->fragment('linked-sources');
        $sourceRecords = $landowner->sourceRecords()->orderByDesc('id')
            ->paginate(15, ['*'], 'sources_page')->withQueryString()->fragment('linked-sources');

        $selectedParcelId = request()->session()->getOldInput('parcel_id');
        $selectedParcel = $selectedParcelId
            ? Parcel::query()->find($selectedParcelId)
            : null;

        $hectareSummary = $hectareValidator->forLandowner($landowner);

        $relatedApplications = LandTransferApplication::query()
            ->linkedToLandownerIds([$landowner->id])
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'applications_page')->withQueryString()->fragment('related-applications');

        return view('staff.records.landowner-show', compact(
            'landowner',
            'selectedParcel',
            'hectareSummary',
            'relatedApplications',
            'landholdings', 'activeHoldingCount', 'otherHoldingCount', 'sourcePackages', 'sourceRecords'
        ));
    }

    public function edit(Landowner $landowner)
    {
        $landowner->load('user');

        $selectedUser = $this->selectedUser(request()->old('user_id', $landowner->user_id), $landowner);

        return view('staff.records.landowner-edit', [
            'landowner' => $landowner,
            'selectedUser' => $selectedUser,
            'registeredOwnerStatusOptions' => Landowner::registeredOwnerStatusOptions(),
        ]);
    }

    public function update(Request $request, Landowner $landowner)
    {
        $expectedRevision = (int) $request->validate([
            'expected_record_revision' => ['required', 'integer', 'min:1'],
        ])['expected_record_revision'];
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'registered_owner_status' => ['nullable', Rule::in(array_keys(Landowner::registeredOwnerStatusOptions()))],
            'spouse_name' => ['nullable', 'string', 'max:255', 'required_if:registered_owner_status,'.Landowner::STATUS_MARRIED],
            'contact_number' => ['nullable', 'string', 'max:100'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'user_id' => [
                'nullable',
                'exists:users,id',
                Rule::unique('landowners', 'user_id')->ignore($landowner->id),
            ],
        ]);

        if (($validated['registered_owner_status'] ?? null) !== Landowner::STATUS_MARRIED) {
            $validated['spouse_name'] = null;
        }

        $landowner = DB::transaction(function () use ($landowner, $validated, $expectedRevision) {
            $lockedLandowner = Landowner::query()
                ->whereKey($landowner->id)
                ->lockForUpdate()
                ->firstOrFail();

            app(\App\Services\RecordEditRevisionService::class)->assertExpected($lockedLandowner, $expectedRevision);

            $oldValues = $lockedLandowner->only(array_keys($validated));
            $lockedLandowner->update($validated);

            AuditLogger::record(
                'landowner_record_updated',
                null,
                $lockedLandowner,
                [
                    'old_values' => $oldValues,
                    'new_values' => $lockedLandowner->fresh()->only(array_keys($validated)),
                    'scope_note' => 'Administrative landowner/person record update only. Current hectares are computed from active landholding records and were not directly edited.',
                ]
            );

            return $lockedLandowner;
        });

        return redirect()
            ->route('staff.records.landowners.show', $landowner)
            ->with('success', 'Landowner record updated successfully. Current hectares remain computed from active landholding records.');
    }
    public function create()
    {
        $selectedUser = $this->selectedUser(request()->old('user_id'));

        return view('staff.records.landowner-create', [
            'selectedUser' => $selectedUser,
            'registeredOwnerStatusOptions' => Landowner::registeredOwnerStatusOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'registered_owner_status' => ['nullable', Rule::in(array_keys(Landowner::registeredOwnerStatusOptions()))],
            'spouse_name' => ['nullable', 'string', 'max:255', 'required_if:registered_owner_status,'.Landowner::STATUS_MARRIED],
            'contact_number' => ['nullable', 'string', 'max:100'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'user_id' => [
                'nullable',
                'exists:users,id',
                Rule::unique('landowners', 'user_id'),
            ],
        ]);

        if (($validated['registered_owner_status'] ?? null) !== Landowner::STATUS_MARRIED) {
            $validated['spouse_name'] = null;
        }

        $landowner = DB::transaction(function () use ($validated) {
            $landowner = Landowner::create($validated);

            AuditLogger::record(
                'landowner_record_created',
                null,
                $landowner,
                [
                    'new_values' => $landowner->only(array_keys($validated)),
                    'scope_note' => 'Administrative landowner/person record creation only. Landholding and parcel linkage must be encoded separately.',
                ]
            );

            return $landowner;
        });

        return redirect()
            ->route('staff.records.landowners.show', $landowner)
            ->with('success', 'Landowner record created successfully. Add landholding records separately when needed.');
    }
    private function selectedUser($userId, ?Landowner $landowner = null): ?User
    {
        if (! $userId) {
            return null;
        }
        return User::query()->where('role', User::ROLE_LANDOWNER)
            ->where(function ($query) use ($landowner) {
                $query->whereDoesntHave('landowner');
                if ($landowner?->user_id) {
                    $query->orWhereKey($landowner->user_id);
                }
            })->find($userId);
    }

}
