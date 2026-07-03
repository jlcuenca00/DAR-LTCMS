<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LandholdingAreaValidationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LandownerRecordController extends Controller
{
    public function show(Landowner $landowner, LandholdingAreaValidationService $hectareValidator)
    {
        $landowner->load([
            'user',
            'landholdings.parcel',
            'landholdings.sourceApplication',
            'sourceRecords',
            'sourceRecordPackages',
            'transferorApplications',
            'transfereeApplications',
        ]);

        $parcels = Parcel::query()
            ->orderBy('parcel_code')
            ->limit(500)
            ->get(['id', 'parcel_code', 'title_no', 'municipality', 'barangay', 'area_hectares']);

        $hectareSummary = $hectareValidator->forLandowner($landowner);

        return view('staff.records.landowner-show', compact(
            'landowner',
            'parcels',
            'hectareSummary'
        ));
    }

    public function edit(Landowner $landowner)
    {
        $landowner->load('user');

        $landownerUsers = User::query()
            ->where('role', 'landowner')
            ->where(function ($query) use ($landowner) {
                $query->whereDoesntHave('landowner')
                    ->orWhere('id', $landowner->user_id);
            })
            ->orderBy('name')
            ->get();

        return view('staff.records.landowner-edit', [
            'landowner' => $landowner,
            'landownerUsers' => $landownerUsers,
            'registeredOwnerStatusOptions' => Landowner::registeredOwnerStatusOptions(),
        ]);
    }

    public function update(Request $request, Landowner $landowner)
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
                Rule::unique('landowners', 'user_id')->ignore($landowner->id),
            ],
        ]);

        if (($validated['registered_owner_status'] ?? null) !== Landowner::STATUS_MARRIED) {
            $validated['spouse_name'] = null;
        }

        $oldValues = $landowner->only(array_keys($validated));

        $landowner->update($validated);

        AuditLogger::record(
            'landowner_record_updated',
            null,
            $landowner,
            [
                'old_values' => $oldValues,
                'new_values' => $landowner->fresh()->only(array_keys($validated)),
                'scope_note' => 'Administrative landowner/person record update only. Current hectares are computed from active landholding records and were not directly edited.',
            ]
        );

        return redirect()
            ->route('staff.records.landowners.show', $landowner)
            ->with('success', 'Landowner record updated successfully. Current hectares remain computed from active landholding records.');
    }
    public function create()
    {
        $landownerUsers = User::query()
            ->where('role', 'landowner')
            ->whereDoesntHave('landowner')
            ->orderBy('name')
            ->get();

        return view('staff.records.landowner-create', [
            'landownerUsers' => $landownerUsers,
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

        return redirect()
            ->route('staff.records.landowners.show', $landowner)
            ->with('success', 'Landowner record created successfully. Add landholding records separately when needed.');
    }
}
