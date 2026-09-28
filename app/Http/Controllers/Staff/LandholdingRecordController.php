<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class LandholdingRecordController extends Controller
{
    public function store(Request $request, Landowner $landowner)
    {
        $validated = $this->validatedData($request);
        $validated = $this->storeReferencePhoto($request, $validated, 'reference-photos/landholdings');
        $newReferencePhotoPath = $validated['reference_photo_path'] ?? null;

        try {
            $landholding = DB::transaction(function () use ($landowner, $validated) {
                $landholding = $landowner->landholdings()->create($validated);

                AuditLogger::record(
                    'landholding_record_created',
                    null,
                    $landholding,
                    [
                        'landowner_id' => $landowner->id,
                        'area_hectares' => $landholding->area_hectares,
                        'status' => $landholding->status,
                        'scope_note' => 'Administrative landholding record encoded for monitoring and assistive hectare validation only. This does not mutate registry records or execute ownership transfer.',
                    ]
                );

                return $landholding;
            });
        } catch (Throwable $e) {
            if ($newReferencePhotoPath) {
                Storage::disk('public')->delete($newReferencePhotoPath);
            }

            throw $e;
        }

        return redirect()
            ->to(route('staff.records.landowners.show', $landowner) . '#landholdings')
            ->with('success', 'Landholding record added. Current hectares were recalculated from active landholding records.');
    }

    public function update(Request $request, Landowner $landowner, Landholding $landholding)
    {
        abort_unless((int) $landholding->landowner_id === (int) $landowner->id, 404);

        $validated = $this->validatedData($request);
        $oldReferencePhotoPath = $landholding->reference_photo_path;
        $validated = $this->storeReferencePhoto($request, $validated, 'reference-photos/landholdings');
        $newReferencePhotoPath = $validated['reference_photo_path'] ?? null;
        $oldValues = $landholding->only(array_keys($validated));

        try {
            DB::transaction(function () use ($landholding, $validated, $landowner, $oldValues) {
                $landholding->update($validated);

                AuditLogger::record(
                    'landholding_record_updated',
                    null,
                    $landholding,
                    [
                        'landowner_id' => $landowner->id,
                        'old_values' => $oldValues,
                        'new_values' => $landholding->fresh()->only(array_keys($validated)),
                        'scope_note' => 'Administrative landholding record update only. This does not automatically transfer land ownership or mutate registry records.',
                    ]
                );
            });
        } catch (Throwable $e) {
            if ($newReferencePhotoPath) {
                Storage::disk('public')->delete($newReferencePhotoPath);
            }

            throw $e;
        }

        if ($newReferencePhotoPath && $oldReferencePhotoPath && $oldReferencePhotoPath !== $newReferencePhotoPath) {
            Storage::disk('public')->delete($oldReferencePhotoPath);
        }

        return redirect()
            ->to(route('staff.records.landowners.show', $landowner) . '#landholding-' . $landholding->id)
            ->with('success', 'Landholding record updated. Current hectares were recalculated from active landholding records.');
    }

    private function storeReferencePhoto(Request $request, array $validated, string $directory): array
    {
        unset($validated['reference_photo']);

        if ($request->hasFile('reference_photo')) {
            $validated['reference_photo_path'] = $request->file('reference_photo')->store($directory, 'public');
        }

        return $validated;
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'parcel_id' => ['required', 'exists:parcels,id'],
            'area_hectares' => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
            'status' => ['required', Rule::in(Landholding::STATUSES)],
            'date_acquired' => ['nullable', 'date'],
            'date_transferred' => ['nullable', 'date'],
            'source_application_id' => ['nullable', 'exists:land_transfer_applications,id'],
            'source_reference_number' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'reference_photo' => ['nullable', 'image', 'max:5120'],
        ]);
    }
}
