<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationParcel;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\ParcelConcurrencyService;
use App\Services\ParcelGeometryService;
use App\Services\ProtectedAdministrativeStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            $search = mb_strtolower($filters['search']);
            $pattern = '%'.collect(preg_split('/\\s+/u', $search))
                ->filter()
                ->implode('%').'%';

            $landownersQuery->whereRaw(
                "LOWER(COALESCE(first_name, '') || ' ' || COALESCE(middle_name, '') || ' ' || COALESCE(last_name, '') || ' ' || COALESCE(registered_owner_status, '') || ' ' || COALESCE(spouse_name, '') || ' ' || COALESCE(contact_number, '') || ' ' || COALESCE(address_line, '')) LIKE ?",
                [$pattern]
            );
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
            $search = mb_strtolower($filters['search']);

            $parcelsQuery->whereRaw(
                "LOWER(COALESCE(parcel_code, '') || ' ' || COALESCE(title_no, '') || ' ' || COALESCE(tax_decl_no, '') || ' ' || COALESCE(lot_number, '') || ' ' || COALESCE(survey_plan_number, '') || ' ' || COALESCE(rod_office, '') || ' ' || COALESCE(remarks, '')) LIKE ?",
                ["%{$search}%"]
            );
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
            $newReferencePhotoPath = $request->file('reference_photo')->store('reference-photos/parcels', ProtectedAdministrativeStorage::PRIVATE_DISK);
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
                app(ProtectedAdministrativeStorage::class)->delete($newReferencePhotoPath);
            }

            throw $e;
        }

        return redirect()
            ->route('staff.records.parcels.show', $parcel)
            ->with('success', 'Parcel record created successfully.');
    }

    public function showParcel(Parcel $parcel)
    {
        $landholdings = $parcel->landholdings()->with(['landowner', 'sourceApplication'])
            ->orderByDesc('id')->paginate(15, ['*'], 'holdings_page')->withQueryString()->fragment('parcel-holdings');
        $activeHoldingCount = $parcel->landholdings()->where('status', Landholding::STATUS_ACTIVE)->count();
        $activeArea = $parcel->landholdings()->where('status', Landholding::STATUS_ACTIVE)->sum('area_hectares');
        $sourcePackages = $parcel->sourceRecordPackages()->withCount('records')->orderByDesc('id')
            ->paginate(15, ['*'], 'packages_page')->withQueryString()->fragment('parcel-sources');
        $legacyRecords = $parcel->legacyRecords()->with('package')->orderByDesc('id')
            ->paginate(15, ['*'], 'sources_page')->withQueryString()->fragment('parcel-sources');

        return view('staff.records.parcel-show', compact(
            'parcel', 'landholdings', 'activeHoldingCount', 'activeArea', 'sourcePackages', 'legacyRecords'
        ));
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
            'geometry_version' => ['required', 'integer', 'min:0'],
            'expected_record_revision' => ['required', 'integer', 'min:1'],
            'reference_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $data['province'] = $data['province'] ?: 'Negros Oriental';
        $data = $this->normalizeParcelRegistrationData($data);
        $data['geometry_geojson'] = app(ParcelGeometryService::class)->decodePolygon($data['geometry_geojson'] ?? null);
        $submittedGeometryVersion = (int) $data['geometry_version'];
        $expectedRevision = (int) $data['expected_record_revision'];
        unset($data['geometry_version'], $data['expected_record_revision']);

        // Keep the existing internal classification value. Staff no longer edits this as a clearance workflow field.
        $data['agricultural_status'] = $parcel->agricultural_status ?: Parcel::DEFAULT_AGRICULTURAL_STATUS;

        unset($data['reference_photo']);

        $oldReferencePhotoPath = null;
        $newReferencePhotoPath = null;

        if ($request->hasFile('reference_photo')) {
            $newReferencePhotoPath = $request->file('reference_photo')->store('reference-photos/parcels', ProtectedAdministrativeStorage::PRIVATE_DISK);
            $data['reference_photo_path'] = $newReferencePhotoPath;
        }

        try {
            $savedParcel = DB::transaction(function () use ($parcel, $data, $request, $submittedGeometryVersion, $expectedRevision, &$oldReferencePhotoPath) {
                $lockedParcel = app(ParcelConcurrencyService::class)->lockParcel((int) $parcel->id);
                app(\App\Services\RecordEditRevisionService::class)->assertExpected($lockedParcel, $expectedRevision);
                $oldReferencePhotoPath = $lockedParcel->reference_photo_path;
                $previousGeometry = $lockedParcel->geometry_geojson;
                $previousVersion = (int) $lockedParcel->geometry_version;

                if ($submittedGeometryVersion !== $previousVersion) {
                    throw ValidationException::withMessages([
                        'geometry_geojson' => 'This Parcel changed after you opened the edit page. Reload the latest record before saving so newer geometry is not overwritten.',
                    ]);
                }

                $lockedParcel->fill($data);
                $lockedParcel->save();

                if ($lockedParcel->wasChanged('geometry_geojson')) {
                    app(ParcelGeometryService::class)->recordRevision(
                        $lockedParcel,
                        $previousGeometry,
                        $previousVersion,
                        $request->user(),
                        'staff_edit'
                    );
                }

                AuditLogger::record(
                    'parcel_updated',
                    null,
                    $lockedParcel,
                    [
                        'parcel_id' => $lockedParcel->id,
                        'parcel_code' => $lockedParcel->parcel_code,
                        'status' => $lockedParcel->status,
                        'has_geometry' => ! empty($lockedParcel->geometry_geojson),
                        'previous_geometry_version' => $previousVersion,
                        'new_geometry_version' => (int) $lockedParcel->geometry_version,
                        'actor_user_id' => $request->user()?->id,
                        'actor_name' => $request->user()?->name,
                    ]
                );

                return $lockedParcel;
            });
        } catch (Throwable $e) {
            if ($newReferencePhotoPath) {
                app(ProtectedAdministrativeStorage::class)->delete($newReferencePhotoPath);
            }

            throw $e;
        }

        if ($newReferencePhotoPath && $oldReferencePhotoPath && $oldReferencePhotoPath !== $newReferencePhotoPath) {
            app(ProtectedAdministrativeStorage::class)->delete($oldReferencePhotoPath);
        }

        app(NotificationService::class)->notifyGeodeticParcelReferenceUpdated($savedParcel);

        return redirect()
            ->route('staff.records.parcels.show', $savedParcel)
            ->with('success', 'Parcel record updated successfully.');
    }

    public function destroyParcel(Request $request, Parcel $parcel)
    {
        $result = DB::transaction(function () use ($request, $parcel) {
            $lockedParcel = app(ParcelConcurrencyService::class)->lockParcel((int) $parcel->id);

            if ($lockedParcel->landholdings()->where('status', Landholding::STATUS_ACTIVE)->exists()) {
                return ['archived' => false, 'reason' => 'active_landholdings', 'parcel' => $lockedParcel];
            }

            $finalStatuses = array_values(array_unique(array_merge(
                LandTransferApplication::FINAL_STATUSES,
                LandTransferApplication::LEGACY_FINAL_STATUSES
            )));

            $hasOpenApplication = ApplicationParcel::query()
                ->where('parcel_id', $lockedParcel->id)
                ->whereHas('application', fn ($query) => $query->whereNotIn('status', $finalStatuses))
                ->exists();

            if ($hasOpenApplication) {
                return ['archived' => false, 'reason' => 'open_application', 'parcel' => $lockedParcel];
            }

            $oldStatus = $lockedParcel->status;

            $lockedParcel->forceFill([
                'status' => 'inactive',
                'remarks' => trim(($lockedParcel->remarks ? $lockedParcel->remarks . "\n\n" : '') . 'Archived by staff on ' . now()->timezone('Asia/Manila')->format('M d, Y h:i A') . '. Record retained for traceability.'),
            ])->save();

            AuditLogger::record(
                'parcel_archived',
                null,
                $lockedParcel,
                [
                    'parcel_id' => $lockedParcel->id,
                    'parcel_code' => $lockedParcel->parcel_code,
                    'old_status' => $oldStatus,
                    'new_status' => $lockedParcel->status,
                    'actor_user_id' => $request->user()?->id,
                    'actor_name' => $request->user()?->name,
                    'archive_policy' => 'Record retained; no ownership or registry mutation performed.',
                ]
            );

            return ['archived' => true, 'parcel' => $lockedParcel];
        });

        if (! $result['archived']) {
            $message = $result['reason'] === 'open_application'
                ? 'This Parcel cannot be archived while an open clearance application still depends on it. Finalize the application or resolve the Parcel link first.'
                : 'This Parcel cannot be archived while active Landholding records remain. Resolve or deactivate those Landholding records first.';

            return redirect()
                ->route('staff.records.parcels.show', $result['parcel'])
                ->with('error', $message);
        }

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
