<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $parcelsQuery = Parcel::query()
            ->latest();

        if (! empty($filters['search'])) {
            $search = strtolower($filters['search']);

            $parcelsQuery->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(parcel_code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(title_no) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(tax_decl_no) LIKE ?', ["%{$search}%"])
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

        $statuses = Parcel::query()
            ->whereNotNull('status')
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

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
            'parcelStatuses' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'linked_application' => 'Linked to Application',
                'flagged' => 'Flagged for Review',
            ],
        ]);
    }

    public function storeParcel(Request $request)
    {
        $data = $request->validate([
            'parcel_code' => ['required', 'string', 'max:255', Rule::unique('parcels', 'parcel_code')],
            'title_no' => ['nullable', 'string', 'max:255'],
            'tax_decl_no' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'area_hectares' => ['nullable', 'numeric', 'min:0', 'max:999999.9999'],
            'status' => ['required', Rule::in(['active', 'inactive', 'linked_application', 'flagged'])],
            'geometry_geojson' => ['nullable', 'json'],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'reference_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $data['province'] = $data['province'] ?: 'Negros Oriental';
        // DAR clearance workflow is limited to agricultural land records.
        // Classification is not a reviewer decision field here; keep the internal default only.
        $data['agricultural_status'] = Parcel::DEFAULT_AGRICULTURAL_STATUS;
        $data['geometry_geojson'] = filled($data['geometry_geojson'] ?? null)
            ? json_decode($data['geometry_geojson'], true)
            : null;

        unset($data['reference_photo']);
        if ($request->hasFile('reference_photo')) {
            $data['reference_photo_path'] = $request->file('reference_photo')->store('reference-photos/parcels', 'public');
        }

        $parcel = Parcel::create($data);

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
            'parcelStatuses' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'linked_application' => 'Linked to Application',
                'flagged' => 'Flagged for Review',
            ],
        ]);
    }

    public function updateParcel(Request $request, Parcel $parcel)
    {
        $data = $request->validate([
            'parcel_code' => ['required', 'string', 'max:255', Rule::unique('parcels', 'parcel_code')->ignore($parcel->id)],
            'title_no' => ['nullable', 'string', 'max:255'],
            'tax_decl_no' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'area_hectares' => ['nullable', 'numeric', 'min:0', 'max:999999.9999'],
            'status' => ['required', Rule::in(['active', 'inactive', 'linked_application', 'flagged'])],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'reference_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        // Keep the existing internal classification value. Staff no longer edits this as a clearance workflow field.
        $data['agricultural_status'] = $parcel->agricultural_status ?: Parcel::DEFAULT_AGRICULTURAL_STATUS;

        unset($data['reference_photo']);
        if ($request->hasFile('reference_photo')) {
            $data['reference_photo_path'] = $request->file('reference_photo')->store('reference-photos/parcels', 'public');
        }

        $parcel->fill($data);
        $parcel->save();

        app(NotificationService::class)->notifyGeodeticParcelReferenceUpdated($parcel);

        return redirect()
            ->route('staff.records.parcels.show', $parcel)
            ->with('success', 'Parcel record updated successfully.');
    }

}