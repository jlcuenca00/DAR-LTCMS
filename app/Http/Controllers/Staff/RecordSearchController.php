<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\ParcelGeometryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class RecordSearchController extends Controller
{
    public function landowners(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'linked_status' => ['nullable', 'string', Rule::in(['linked', 'unlinked'])],
        ]);

        $landownersQuery = Landowner::query()
            ->with('user')
            ->withCount(['landholdings as active_landholding_count' => function ($query) {
                $query->where('status', 'active');
            }])
            ->withSum(['landholdings as active_landholding_area_hectares' => function ($query) {
                $query->where('status', 'active');
            }], 'area_hectares')
            ->latest();

        if (! empty($filters['search'])) {
            $search = strtolower($filters['search']);

            $landownersQuery->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(first_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(middle_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(registered_owner_status) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(spouse_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(contact_number) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(address_line) LIKE ?', ["%{$search}%"]);
            });
        }

        if (! empty($filters['municipality'])) {
            $landownersQuery->where('municipality', $filters['municipality']);
        }

        if (! empty($filters['barangay'])) {
            $landownersQuery->where('barangay', $filters['barangay']);
        }

        if (($filters['linked_status'] ?? null) === 'linked') {
            $landownersQuery->whereNotNull('user_id');
        }

        if (($filters['linked_status'] ?? null) === 'unlinked') {
            $landownersQuery->whereNull('user_id');
        }

        $landowners = $landownersQuery
            ->paginate(15)
            ->withQueryString();

        $municipalities = Landowner::query()
            ->whereNotNull('municipality')
            ->select('municipality')
            ->distinct()
            ->orderBy('municipality')
            ->pluck('municipality');

        $barangays = Landowner::query()
            ->whereNotNull('barangay')
            ->when(! empty($filters['municipality']), function ($query) use ($filters) {
                $query->where('municipality', $filters['municipality']);
            })
            ->select('barangay')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay');

        $fiveHectareLimit = 5.0000;

        $totalActiveLandholdingArea = Landholding::query()
            ->where('status', 'active')
            ->sum('area_hectares');

        return view('staff.records.landowners', compact(
            'landowners',
            'filters',
            'municipalities',
            'barangays',
            'fiveHectareLimit',
            'totalActiveLandholdingArea'
        ));
    }

    public function parcels(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_keys(Parcel::statusOptions()))],
        ]);

        $parcelsQuery = Parcel::query()
            ->latest();

        if (! empty($filters['search'])) {
            $search = strtolower($filters['search']);

            $parcelsQuery->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(parcel_code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(title_no) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(tax_decl_no) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(lot_number) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(survey_plan_number) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(rod_office) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(remarks) LIKE ?', ["%{$search}%"]);
            });
        }

        if (! empty($filters['municipality'])) {
            $parcelsQuery->where('municipality', $filters['municipality']);
        }

        if (! empty($filters['barangay'])) {
            $parcelsQuery->where('barangay', $filters['barangay']);
        }

        if (! empty($filters['status'])) {
            $parcelsQuery->where('status', $filters['status']);
        }

        $parcels = $parcelsQuery
            ->paginate(15)
            ->withQueryString();

        $municipalities = Parcel::query()
            ->whereNotNull('municipality')
            ->select('municipality')
            ->distinct()
            ->orderBy('municipality')
            ->pluck('municipality');

        $barangays = Parcel::query()
            ->whereNotNull('barangay')
            ->when(! empty($filters['municipality']), function ($query) use ($filters) {
                $query->where('municipality', $filters['municipality']);
            })
            ->select('barangay')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay');

        $statuses = collect(array_keys(Parcel::statusOptions()));

        return view('staff.records.parcels', compact(
            'parcels',
            'filters',
            'municipalities',
            'barangays',
            'statuses'
        ));
    }

    public function createParcel()
    {
        return view('staff.records.parcel-create', [
            'parcelStatuses' => Parcel::statusOptions(),
            'titleTypes' => Parcel::titleTypeOptions(),
            'rodOffices' => Parcel::rodOfficeOptions(),
        ]);
    }

    public function storeParcel(Request $request)
    {
        $data = $request->validate([
            'parcel_code' => ['required', 'string', 'max:255', Rule::unique('parcels', 'parcel_code')],
            'title_no' => ['nullable', 'string', 'max:255'],
            'tax_decl_no' => ['nullable', 'string', 'max:255'],
            'lot_number' => ['nullable', 'string', 'max:255'],
            'survey_plan_number' => ['nullable', 'string', 'max:255'],
            'title_type' => ['nullable', Rule::in(array_keys(Parcel::titleTypeOptions()))],
            'rod_office' => ['nullable', Rule::in(array_keys(Parcel::rodOfficeOptions()))],
            'province' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'area_hectares' => ['nullable', 'numeric', 'min:0', 'max:999999.9999'],
            'area_square_meters' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'status' => ['required', Rule::in(array_keys(Parcel::statusOptions()))],
            'geometry_geojson' => ['nullable', 'string', 'max:200000'],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'reference_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $data['province'] = $data['province'] ?: 'Negros Oriental';
        $data = $this->normalizeParcelRegistrationData($data);
        // DAR clearance workflow is limited to agricultural land records.
        // Classification is not a reviewer decision field here; keep the internal default only.
        $data['agricultural_status'] = Parcel::DEFAULT_AGRICULTURAL_STATUS;
        $data['geometry_geojson'] = app(ParcelGeometryService::class)->decodePolygon($data['geometry_geojson'] ?? null);

        unset($data['reference_photo']);
        $newReferencePhotoPath = null;

        if ($request->hasFile('reference_photo')) {
            $newReferencePhotoPath = $request->file('reference_photo')->store('reference-photos/parcels', 'public');
            $data['reference_photo_path'] = $newReferencePhotoPath;
        }

        try {
            $parcel = DB::transaction(function () use ($data, $request) {
                $parcel = Parcel::create($data);

                if ($parcel->geometry_geojson !== null) {
                    app(ParcelGeometryService::class)->recordRevision(
                        $parcel,
                        null,
                        0,
                        $request->user(),
                        'staff_create'
                    );
                }

                AuditLogger::record(
                    'parcel_created',
                    null,
                    $parcel,
                    [
                        'parcel_id' => $parcel->id,
                        'parcel_code' => $parcel->parcel_code,
                        'municipality' => $parcel->municipality,
                        'barangay' => $parcel->barangay,
                        'area_hectares' => $parcel->area_hectares,
                        'dar_clearance_scope' => 'Agricultural land clearance record only',
                        'has_geometry' => ! empty($parcel->geometry_geojson),
                        'actor_user_id' => $request->user()?->id,
                        'actor_name' => $request->user()?->name,
                    ]
                );

                return $parcel;
            });
        } catch (Throwable $e) {
            if ($newReferencePhotoPath) {
                Storage::disk('public')->delete($newReferencePhotoPath);
            }

            throw $e;
        }

        return redirect()
            ->route('staff.records.parcels.show', $parcel)
            ->with('success', 'Parcel record created successfully.');
    }

    public function showParcel(Parcel $parcel)
    {
        $parcel->load([
            'landholdings.landowner',
            'landholdings.sourceApplication',
            'legacyRecords.package',
            'sourceRecordPackages.records',
        ]);

        return view('staff.records.parcel-show', compact('parcel'));
    }

    public function editParcel(Parcel $parcel)
    {
        return view('staff.records.parcel-edit', [
            'parcel' => $parcel,
            'parcelStatuses' => Parcel::statusOptions(),
            'titleTypes' => Parcel::titleTypeOptions(),
            'rodOffices' => Parcel::rodOfficeOptions(),
        ]);
    }

    public function updateParcel(Request $request, Parcel $parcel)
    {
        $data = $request->validate([
            'parcel_code' => ['required', 'string', 'max:255', Rule::unique('parcels', 'parcel_code')->ignore($parcel->id)],
            'title_no' => ['nullable', 'string', 'max:255'],
            'tax_decl_no' => ['nullable', 'string', 'max:255'],
            'lot_number' => ['nullable', 'string', 'max:255'],
            'survey_plan_number' => ['nullable', 'string', 'max:255'],
            'title_type' => ['nullable', Rule::in(array_keys(Parcel::titleTypeOptions()))],
            'rod_office' => ['nullable', Rule::in(array_keys(Parcel::rodOfficeOptions()))],
            'province' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'area_hectares' => ['nullable', 'numeric', 'min:0', 'max:999999.9999'],
            'area_square_meters' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'status' => ['required', Rule::in(array_keys(Parcel::statusOptions()))],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'geometry_geojson' => ['nullable', 'string', 'max:200000'],
            'reference_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $data['province'] = $data['province'] ?: 'Negros Oriental';
        $data = $this->normalizeParcelRegistrationData($data);
        $data['geometry_geojson'] = app(ParcelGeometryService::class)->decodePolygon($data['geometry_geojson'] ?? null);

        // Keep the existing internal classification value. Staff no longer edits this as a clearance workflow field.
        $data['agricultural_status'] = $parcel->agricultural_status ?: Parcel::DEFAULT_AGRICULTURAL_STATUS;

        unset($data['reference_photo']);

        $oldReferencePhotoPath = $parcel->reference_photo_path;
        $newReferencePhotoPath = null;

        if ($request->hasFile('reference_photo')) {
            $newReferencePhotoPath = $request->file('reference_photo')->store('reference-photos/parcels', 'public');
            $data['reference_photo_path'] = $newReferencePhotoPath;
        }

        try {
            DB::transaction(function () use ($parcel, $data, $request) {
                $previousGeometry = $parcel->geometry_geojson;
                $previousVersion = (int) $parcel->geometry_version;

                $parcel->fill($data);
                $parcel->save();

                if ($parcel->wasChanged('geometry_geojson')) {
                    app(ParcelGeometryService::class)->recordRevision(
                        $parcel,
                        $previousGeometry,
                        $previousVersion,
                        $request->user(),
                        'staff_edit'
                    );
                }

                AuditLogger::record(
                    'parcel_updated',
                    null,
                    $parcel,
                    [
                        'parcel_id' => $parcel->id,
                        'parcel_code' => $parcel->parcel_code,
                        'status' => $parcel->status,
                        'has_geometry' => ! empty($parcel->geometry_geojson),
                        'actor_user_id' => $request->user()?->id,
                        'actor_name' => $request->user()?->name,
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

        app(NotificationService::class)->notifyGeodeticParcelReferenceUpdated($parcel);

        return redirect()
            ->route('staff.records.parcels.show', $parcel)
            ->with('success', 'Parcel record updated successfully.');
    }

    public function destroyParcel(Request $request, Parcel $parcel)
    {
        $oldStatus = $parcel->status;

        $parcel->forceFill([
            'status' => 'inactive',
            'remarks' => trim(($parcel->remarks ? $parcel->remarks . "\n\n" : '') . 'Archived by staff on ' . now()->timezone('Asia/Manila')->format('M d, Y h:i A') . '. Record retained for traceability.'),
        ])->save();

        AuditLogger::record(
            'parcel_archived',
            null,
            $parcel,
            [
                'parcel_id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code,
                'old_status' => $oldStatus,
                'new_status' => $parcel->status,
                'actor_user_id' => $request->user()?->id,
                'actor_name' => $request->user()?->name,
                'archive_policy' => 'Record retained; no ownership or registry mutation performed.',
            ]
        );

        return redirect()
            ->route('staff.records.parcels.index')
            ->with('success', 'Parcel record archived. The record was retained for traceability and audit review.');
    }

    private function normalizeParcelRegistrationData(array $data): array
    {
        if (array_key_exists('area_square_meters', $data) && filled($data['area_square_meters'])) {
            $data['area_square_meters'] = round((float) $data['area_square_meters'], 2);

            if (empty($data['area_hectares'])) {
                $data['area_hectares'] = round($data['area_square_meters'] / 10000, 4);
            }
        }

        if (array_key_exists('area_hectares', $data) && filled($data['area_hectares'])) {
            $data['area_hectares'] = round((float) $data['area_hectares'], 4);

            if (empty($data['area_square_meters'])) {
                $data['area_square_meters'] = round($data['area_hectares'] * 10000, 2);
            }
        }

        return $data;
    }

}
