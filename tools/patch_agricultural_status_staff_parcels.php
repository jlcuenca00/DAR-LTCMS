<?php

$root = dirname(__DIR__);

function patch_path(string $relative): string
{
    global $root;
    return $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
}

function read_file_required(string $relative): string
{
    $path = patch_path($relative);

    if (! file_exists($path)) {
        fwrite(STDERR, "ERROR: {$relative} not found. Make sure you extracted this patch into the Laravel project root.\n");
        exit(1);
    }

    return file_get_contents($path);
}

function write_file_if_changed(string $relative, string $contents): void
{
    $path = patch_path($relative);
    $old = file_exists($path) ? file_get_contents($path) : null;

    if ($old !== $contents) {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $contents);
        echo "Updated {$relative}\n";
    } else {
        echo "No change {$relative}\n";
    }
}

function insert_before_last_class_brace(string $contents, string $insert): string
{
    $pos = strrpos($contents, "\n}");

    if ($pos === false) {
        fwrite(STDERR, "ERROR: Could not find class closing brace for insertion.\n");
        exit(1);
    }

    return substr($contents, 0, $pos) . "\n" . $insert . substr($contents, $pos);
}

// Safety check: foundation patch must already be applied.
$parcelModel = read_file_required('app/Models/Parcel.php');
if (! str_contains($parcelModel, 'AGRICULTURAL_STATUSES') || ! str_contains($parcelModel, 'agriculturalStatusOptions')) {
    fwrite(STDERR, "ERROR: Parcel model agricultural status helpers were not found. Apply the foundation patch first.\n");
    exit(1);
}

// 1) Patch staff parcel record controller.
$controllerRelative = 'app/Http/Controllers/Staff/RecordSearchController.php';
$controller = read_file_required($controllerRelative);
$originalController = $controller;

if (! str_contains($controller, 'use App\\Services\\AuditLogger;')) {
    $controller = str_replace("use App\\Models\\Parcel;\n", "use App\\Models\\Parcel;\nuse App\\Services\\AuditLogger;\n", $controller);
}

if (! str_contains($controller, "'agricultural_status' => ['nullable', 'string', Rule::in(array_keys(Parcel::agriculturalStatusOptions()))]")) {
    $controller = str_replace(
        "'status' => ['nullable', 'string', 'max:50'],",
        "'status' => ['nullable', 'string', 'max:50'],\n            'agricultural_status' => ['nullable', 'string', Rule::in(array_keys(Parcel::agriculturalStatusOptions()))],",
        $controller
    );
}

if (! str_contains($controller, "where('agricultural_status', $" . "filters['agricultural_status']")) {
    $controller = str_replace(
        "        if (! empty($" . "filters['status'])) {\n            $" . "parcelsQuery->where('status', $" . "filters['status']);\n        }",
        "        if (! empty($" . "filters['status'])) {\n            $" . "parcelsQuery->where('status', $" . "filters['status']);\n        }\n\n        if (! empty($" . "filters['agricultural_status'])) {\n            $" . "parcelsQuery->where('agricultural_status', $" . "filters['agricultural_status']);\n        }",
        $controller
    );
}

if (! str_contains($controller, '$agriculturalStatuses = Parcel::agriculturalStatusOptions();')) {
    $controller = str_replace(
        "        $" . "statuses = Parcel::query()\n            ->whereNotNull('status')\n            ->select('status')\n            ->distinct()\n            ->orderBy('status')\n            ->pluck('status');",
        "        $" . "statuses = Parcel::query()\n            ->whereNotNull('status')\n            ->select('status')\n            ->distinct()\n            ->orderBy('status')\n            ->pluck('status');\n\n        $" . "agriculturalStatuses = Parcel::agriculturalStatusOptions();",
        $controller
    );
}

if (! str_contains($controller, "'agriculturalStatuses'")) {
    $controller = str_replace(
        "            'statuses'\n        ));",
        "            'statuses',\n            'agriculturalStatuses'\n        ));",
        $controller
    );
}

if (! str_contains($controller, 'public function editParcel(Parcel $parcel)')) {
    $methods = <<<'PHP_CODE'
    public function editParcel(Parcel $parcel)
    {
        return view('staff.records.parcel-edit', [
            'parcel' => $parcel,
            'agriculturalStatuses' => Parcel::agriculturalStatusOptions(),
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
            'agricultural_status' => ['required', Rule::in(array_keys(Parcel::agriculturalStatusOptions()))],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        $oldAgriculturalStatus = $parcel->agricultural_status ?: 'not_yet_determined';

        $parcel->fill($data);
        $agriculturalStatusChanged = $parcel->isDirty('agricultural_status');
        $parcel->save();

        if ($agriculturalStatusChanged) {
            AuditLogger::record(
                'parcel_agricultural_status_updated',
                null,
                $parcel,
                [
                    'parcel_id' => $parcel->id,
                    'parcel_code' => $parcel->parcel_code,
                    'old_agricultural_status' => $oldAgriculturalStatus,
                    'old_agricultural_status_label' => Parcel::agriculturalStatusLabel($oldAgriculturalStatus),
                    'new_agricultural_status' => $parcel->agricultural_status,
                    'new_agricultural_status_label' => $parcel->agricultural_status_label,
                    'actor_user_id' => $request->user()?->id,
                    'actor_name' => $request->user()?->name,
                ]
            );
        }

        return redirect()
            ->route('staff.records.parcels.show', $parcel)
            ->with('success', 'Parcel record updated successfully.');
    }

PHP_CODE;

    $controller = insert_before_last_class_brace($controller, $methods);
}

if ($controller === $originalController) {
    echo "No change {$controllerRelative}\n";
} else {
    write_file_if_changed($controllerRelative, $controller);
}

// 2) Patch staff parcel routes, keeping edit route before show route.
$routesRelative = 'routes/web.php';
$routes = read_file_required($routesRelative);
$originalRoutes = $routes;

if (! str_contains($routes, "records.parcels.edit")) {
    $oldBlock = <<<'ROUTES'
        Route::get('/records/parcels', [RecordSearchController::class, 'parcels'])
            ->name('records.parcels.index');
        Route::get('/records/parcels/{parcel}', [RecordSearchController::class, 'showParcel'])
            ->name('records.parcels.show');
ROUTES;

    $newBlock = <<<'ROUTES'
        Route::get('/records/parcels', [RecordSearchController::class, 'parcels'])
            ->name('records.parcels.index');
        Route::get('/records/parcels/{parcel}/edit', [RecordSearchController::class, 'editParcel'])
            ->name('records.parcels.edit');
        Route::patch('/records/parcels/{parcel}', [RecordSearchController::class, 'updateParcel'])
            ->name('records.parcels.update');
        Route::get('/records/parcels/{parcel}', [RecordSearchController::class, 'showParcel'])
            ->name('records.parcels.show');
ROUTES;

    if (! str_contains($routes, $oldBlock)) {
        fwrite(STDERR, "ERROR: Could not safely locate staff parcel route block in routes/web.php.\n");
        exit(1);
    }

    $routes = str_replace($oldBlock, $newBlock, $routes);
}

if ($routes === $originalRoutes) {
    echo "No change {$routesRelative}\n";
} else {
    write_file_if_changed($routesRelative, $routes);
}

// 3) Add the staff parcel edit view.
$parcelEditView = <<<'BLADE'
<x-staff-shell
    title="Edit Parcel Record"
    active="parcel-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.records.parcels.show', $parcel) }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Parcel Details
        </a>
    </x-slot>

    @php
        $agriculturalStatuses = $agriculturalStatuses ?? \App\Models\Parcel::agriculturalStatusOptions();
        $parcelStatuses = $parcelStatuses ?? [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'linked_application' => 'Linked to Application',
            'flagged' => 'Flagged for Review',
        ];
    @endphp

    <section class="staff-scope-banner">
        <div>
            <h3>Parcel Record Update</h3>
            <p>
                Update encoded parcel reference information used for DAR review, monitoring, and map display. This does not transfer ownership or mutate registry records.
            </p>
        </div>
        <span class="staff-scope-pill">Reference Data Only</span>
    </section>

    <form method="POST" action="{{ route('staff.records.parcels.update', $parcel) }}" class="space-y-5">
        @csrf
        @method('PATCH')

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <section class="staff-panel staff-panel-pad xl:col-span-2">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
                    <div>
                        <h2 class="staff-panel-title">Parcel Information</h2>
                        <p class="staff-panel-subtitle">Core encoded parcel values used across records, maps, and application review screens.</p>
                    </div>
                    <span class="staff-badge {{ $parcel->status === 'active' ? 'staff-badge-green' : 'staff-badge-slate' }}">
                        {{ $parcel->status ? ucwords(str_replace('_', ' ', $parcel->status)) : 'Status N/A' }}
                    </span>
                </div>

                <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Parcel Code</label>
                        <input type="text" name="parcel_code" value="{{ old('parcel_code', $parcel->parcel_code) }}" required class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                        @error('parcel_code')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Status</label>
                        <select name="status" required class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                            @foreach ($parcelStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $parcel->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Title Number</label>
                        <input type="text" name="title_no" value="{{ old('title_no', $parcel->title_no) }}" class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                        @error('title_no')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Tax Declaration Number</label>
                        <input type="text" name="tax_decl_no" value="{{ old('tax_decl_no', $parcel->tax_decl_no) }}" class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                        @error('tax_decl_no')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Area / Hectares</label>
                        <input type="number" step="0.0001" min="0" name="area_hectares" value="{{ old('area_hectares', $parcel->area_hectares) }}" class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                        @error('area_hectares')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Agricultural Status</label>
                        <select name="agricultural_status" required class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                            @foreach ($agriculturalStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('agricultural_status', $parcel->agricultural_status ?? 'not_yet_determined') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Used for DAR record classification and monitoring. This does not transfer ownership or mutate registry records.</p>
                        @error('agricultural_status')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <aside class="space-y-5">
                <section class="staff-panel staff-panel-pad">
                    <h3 class="staff-panel-title">Location</h3>
                    <p class="staff-panel-subtitle">Administrative location details for search and map reference.</p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Province</label>
                            <input type="text" name="province" value="{{ old('province', $parcel->province ?? 'Negros Oriental') }}" class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                            @error('province')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Municipality</label>
                            <input type="text" name="municipality" value="{{ old('municipality', $parcel->municipality) }}" class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                            @error('municipality')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Barangay</label>
                            <input type="text" name="barangay" value="{{ old('barangay', $parcel->barangay) }}" class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                            @error('barangay')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="staff-panel staff-panel-pad">
                    <h3 class="staff-panel-title">Save Changes</h3>
                    <p class="staff-panel-subtitle">Changes are logged when agricultural status is updated.</p>

                    <div class="mt-4 flex flex-col gap-2">
                        <button type="submit" class="staff-button staff-button-primary justify-center">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Save Parcel Record
                        </button>
                        <a href="{{ route('staff.records.parcels.show', $parcel) }}" class="staff-button staff-button-light justify-center">
                            Cancel
                        </a>
                    </div>
                </section>
            </aside>
        </div>

        <section class="staff-panel staff-panel-pad">
            <h3 class="staff-panel-title">Remarks</h3>
            <p class="staff-panel-subtitle">Optional staff notes for administrative reference.</p>
            <textarea name="remarks" rows="4" class="mt-4 w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">{{ old('remarks', $parcel->remarks) }}</textarea>
            @error('remarks')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </section>
    </form>
</x-staff-shell>
BLADE;

write_file_if_changed('resources/views/staff/records/parcel-edit.blade.php', $parcelEditView);

// 4) Patch staff parcel index view quietly.
$parcelsViewRelative = 'resources/views/staff/records/parcels.blade.php';
$parcelsView = read_file_required($parcelsViewRelative);
$originalParcelsView = $parcelsView;

if (! str_contains($parcelsView, 'agriculturalStatuses')) {
    $parcelsView = preg_replace('/>\s*<x-slot name="actions">/s', ">\n    @php(\$agriculturalStatuses = \$agriculturalStatuses ?? \\App\\Models\\Parcel::agriculturalStatusOptions())\n\n    <x-slot name=\"actions\">", $parcelsView, 1);
}

if (! str_contains($parcelsView, 'name="agricultural_status"')) {
    $statusFilter = <<<'BLADE_FILTER'
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
BLADE_FILTER;

    $statusFilterWithAgricultural = $statusFilter . <<<'BLADE_FILTER'
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Agricultural Status</label>
                <select name="agricultural_status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All classifications</option>
                    @foreach ($agriculturalStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['agricultural_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
BLADE_FILTER;

    $parcelsView = str_replace($statusFilter, $statusFilterWithAgricultural, $parcelsView);
}

if (! str_contains($parcelsView, '$parcel->agricultural_status_label')) {
    $parcelsView = str_replace(
        "<td><span class=\"staff-badge {{ $" . "parcel->status === 'active' ? 'staff-badge-green' : 'staff-badge-slate' }}\">{{ ucwords($" . "parcel->status ?? 'Unspecified') }}</span></td>",
        "<td>\n                                <span class=\"staff-badge {{ $" . "parcel->status === 'active' ? 'staff-badge-green' : 'staff-badge-slate' }}\">{{ ucwords(str_replace('_', ' ', $" . "parcel->status ?? 'Unspecified')) }}</span>\n                                <div class=\"mt-1\">\n                                    <span class=\"staff-badge staff-badge-slate\">{{ $" . "parcel->agricultural_status_label }}</span>\n                                </div>\n                            </td>",
        $parcelsView
    );
}

if (str_contains($parcelsView, "class=\"text-right\"><a href=\"{{ route('staff.records.parcels.show', $" . "parcel) }}\" class=\"staff-button staff-button-light\">View</a></td>")) {
    $parcelsView = str_replace(
        "<td class=\"text-right\"><a href=\"{{ route('staff.records.parcels.show', $" . "parcel) }}\" class=\"staff-button staff-button-light\">View</a></td>",
        "<td class=\"text-right\">\n                                <div class=\"flex flex-wrap justify-end gap-2\">\n                                    <a href=\"{{ route('staff.records.parcels.show', $" . "parcel) }}\" class=\"staff-button staff-button-light\">View</a>\n                                    <a href=\"{{ route('staff.records.parcels.edit', $" . "parcel) }}\" class=\"staff-button staff-button-light\">Edit</a>\n                                </div>\n                            </td>",
        $parcelsView
    );
}

if ($parcelsView === $originalParcelsView) {
    echo "No change {$parcelsViewRelative}\n";
} else {
    write_file_if_changed($parcelsViewRelative, $parcelsView);
}

// 5) Patch staff parcel details view quietly.
$parcelShowRelative = 'resources/views/staff/records/parcel-show.blade.php';
$parcelShow = read_file_required($parcelShowRelative);
$originalParcelShow = $parcelShow;

if (! str_contains($parcelShow, "route('staff.records.parcels.edit'")) {
    $parcelShow = str_replace(
        "        <a href=\"{{ route('staff.records.parcels.index') }}\" class=\"staff-button staff-button-light\">\n            <i class=\"fa-solid fa-arrow-left\"></i>\n            Back to Parcel Records\n        </a>",
        "        <a href=\"{{ route('staff.records.parcels.edit', $" . "parcel) }}\" class=\"staff-button staff-button-primary\">\n            <i class=\"fa-solid fa-pen-to-square\"></i>\n            Edit Record\n        </a>\n\n        <a href=\"{{ route('staff.records.parcels.index') }}\" class=\"staff-button staff-button-light\">\n            <i class=\"fa-solid fa-arrow-left\"></i>\n            Back to Parcel Records\n        </a>",
        $parcelShow
    );
}

if (! str_contains($parcelShow, 'Agricultural Status:')) {
    $parcelShow = str_replace(
        "                <span class=\"staff-badge {{ $" . "parcel->geometry_geojson ? 'staff-badge-green' : 'staff-badge-slate' }}\">\n                    {{ $" . "parcel->geometry_geojson ? 'Mapped Geometry' : 'No Geometry' }}\n                </span>",
        "                <span class=\"staff-badge {{ $" . "parcel->geometry_geojson ? 'staff-badge-green' : 'staff-badge-slate' }}\">\n                    {{ $" . "parcel->geometry_geojson ? 'Mapped Geometry' : 'No Geometry' }}\n                </span>\n\n                <span class=\"staff-badge staff-badge-slate\">\n                    Agricultural Status: {{ $" . "parcel->agricultural_status_label }}\n                </span>",
        $parcelShow
    );
}

if (! str_contains($parcelShow, '<p class="parcel-meta-label">Agricultural Status</p>')) {
    $areaCard = <<<'BLADE_AREA'
                <div class="parcel-meta-card">
                    <p class="parcel-meta-label">Area</p>
                    <p class="parcel-meta-value">{{ number_format((float) $parcel->area_hectares, 4) }} hectares</p>
                </div>
BLADE_AREA;

    $areaCardWithAgricultural = $areaCard . <<<'BLADE_AREA'

                <div class="parcel-meta-card">
                    <p class="parcel-meta-label">Agricultural Status</p>
                    <p class="parcel-meta-value">{{ $parcel->agricultural_status_label }}</p>
                    <p class="parcel-meta-subvalue">Classification for DAR record monitoring.</p>
                </div>
BLADE_AREA;

    $parcelShow = str_replace($areaCard, $areaCardWithAgricultural, $parcelShow);
}

if ($parcelShow === $originalParcelShow) {
    echo "No change {$parcelShowRelative}\n";
} else {
    write_file_if_changed($parcelShowRelative, $parcelShow);
}

// 6) Add feature tests for staff parcel agricultural status update and audit log.
$testFile = <<<'PHP_TEST'
<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParcelAgriculturalStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_update_parcel_agricultural_status_and_audit_log_is_created(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'AGR-STATUS-001',
            'title_no' => 'TCT-AGR-001',
            'tax_decl_no' => 'TD-AGR-001',
            'province' => 'Negros Oriental',
            'municipality' => 'Bayawan City',
            'barangay' => 'Banga',
            'area_hectares' => 2.4000,
            'status' => 'active',
            'agricultural_status' => 'not_yet_determined',
            'remarks' => 'Initial agricultural classification test parcel.',
        ]);

        $response = $this->actingAs($staffUser)
            ->patch(route('staff.records.parcels.update', $parcel), [
                'parcel_code' => 'AGR-STATUS-001',
                'title_no' => 'TCT-AGR-001',
                'tax_decl_no' => 'TD-AGR-001',
                'province' => 'Negros Oriental',
                'municipality' => 'Bayawan City',
                'barangay' => 'Banga',
                'area_hectares' => '2.4000',
                'status' => 'active',
                'agricultural_status' => 'carp_covered',
                'remarks' => 'Updated agricultural classification test parcel.',
            ]);

        $response->assertRedirect(route('staff.records.parcels.show', $parcel));

        $this->assertDatabaseHas('parcels', [
            'id' => $parcel->id,
            'agricultural_status' => 'carp_covered',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staffUser->id,
            'auditable_type' => Parcel::class,
            'auditable_id' => $parcel->id,
            'action' => 'parcel_agricultural_status_updated',
        ]);

        $auditLog = AuditLog::query()
            ->where('action', 'parcel_agricultural_status_updated')
            ->firstOrFail();

        $this->assertSame('not_yet_determined', $auditLog->metadata['old_agricultural_status']);
        $this->assertSame('carp_covered', $auditLog->metadata['new_agricultural_status']);
    }

    public function test_staff_parcel_details_display_agricultural_status_label(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'AGR-DETAILS-001',
            'title_no' => 'TCT-AGR-DETAILS-001',
            'province' => 'Negros Oriental',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'area_hectares' => 1.2500,
            'status' => 'active',
            'agricultural_status' => 'private_agricultural',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.records.parcels.show', $parcel));

        $response->assertOk();
        $response->assertSee('Agricultural Status');
        $response->assertSee('Private Agricultural Land');
    }

    public function test_staff_can_filter_parcels_by_agricultural_status(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        Parcel::create([
            'parcel_code' => 'VISIBLE-AGRI-FILTER',
            'title_no' => 'TCT-VISIBLE-AGRI',
            'province' => 'Negros Oriental',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'area_hectares' => 1.2500,
            'status' => 'active',
            'agricultural_status' => 'awarded_cloa',
        ]);

        Parcel::create([
            'parcel_code' => 'HIDDEN-AGRI-FILTER',
            'title_no' => 'TCT-HIDDEN-AGRI',
            'province' => 'Negros Oriental',
            'municipality' => 'Bayawan City',
            'barangay' => 'Banga',
            'area_hectares' => 2.0000,
            'status' => 'active',
            'agricultural_status' => 'private_agricultural',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.records.parcels.index', [
                'agricultural_status' => 'awarded_cloa',
            ]));

        $response->assertOk();
        $response->assertSee('VISIBLE-AGRI-FILTER');
        $response->assertDontSee('HIDDEN-AGRI-FILTER');
    }
}
PHP_TEST;

write_file_if_changed('tests/Feature/ParcelAgriculturalStatusTest.php', $testFile);

echo "\nAgricultural status staff parcel patch applied. Next run:\n";
echo "php artisan view:clear\n";
echo "php artisan route:clear\n";
echo "php artisan test\n";
