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
        fwrite(STDERR, "ERROR: {$relative} not found. Make sure this package is extracted into the Laravel project root.\n");
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

function replace_once_or_fail(string $contents, string $search, string $replace, string $file, string $label): string
{
    if (! str_contains($contents, $search)) {
        fwrite(STDERR, "ERROR: Could not locate {$label} in {$file}.\n");
        exit(1);
    }

    return str_replace($search, $replace, $contents, $count);
}

// Safety check: foundation model helper must exist.
$parcelModel = read_file_required('app/Models/Parcel.php');
if (! str_contains($parcelModel, 'AGRICULTURAL_STATUSES') || ! str_contains($parcelModel, 'agriculturalStatusOptions')) {
    fwrite(STDERR, "ERROR: Parcel agricultural status helpers were not found. Apply the foundation patch first.\n");
    exit(1);
}

// 1) Patch MonitoringReportController with agricultural status data.
$controllerRelative = 'app/Http/Controllers/Staff/MonitoringReportController.php';
$controller = read_file_required($controllerRelative);
$originalController = $controller;

if (! str_contains($controller, 'use App\\Models\\Parcel;')) {
    $controller = str_replace(
        "use App\\Models\\LandTransferApplication;\n",
        "use App\\Models\\LandTransferApplication;\nuse App\\Models\\Parcel;\n",
        $controller
    );
}

if (! str_contains($controller, '$agriculturalStatusBreakdown = Parcel::query()')) {
    $search = <<<'PHP_CODE'
        $recentClearances = ApplicationClearance::query()
            ->latest('generated_at')
            ->limit(10)
            ->get();

        return [
PHP_CODE;

    $replace = <<<'PHP_CODE'
        $recentClearances = ApplicationClearance::query()
            ->latest('generated_at')
            ->limit(10)
            ->get();

        $agriculturalStatusOptions = Parcel::agriculturalStatusOptions();

        $agriculturalStatusBreakdown = Parcel::query()
            ->selectRaw("COALESCE(agricultural_status, 'not_yet_determined') as agricultural_status, COUNT(*) as total")
            ->groupByRaw("COALESCE(agricultural_status, 'not_yet_determined')")
            ->orderByRaw("COALESCE(agricultural_status, 'not_yet_determined')")
            ->pluck('total', 'agricultural_status');

        return [
PHP_CODE;

    $controller = replace_once_or_fail($controller, $search, $replace, $controllerRelative, 'recent clearances report block');
}

if (! str_contains($controller, "'agriculturalStatusBreakdown' => $" . "agriculturalStatusBreakdown")) {
    $search = <<<'PHP_CODE'
            'recentApplications' => $recentApplications,
            'recentClearances' => $recentClearances,
            'generatedAt' => now(),
PHP_CODE;

    $replace = <<<'PHP_CODE'
            'recentApplications' => $recentApplications,
            'recentClearances' => $recentClearances,
            'agriculturalStatusOptions' => $agriculturalStatusOptions,
            'agriculturalStatusBreakdown' => $agriculturalStatusBreakdown,
            'generatedAt' => now(),
PHP_CODE;

    $controller = replace_once_or_fail($controller, $search, $replace, $controllerRelative, 'report return array insertion point');
}

write_file_if_changed($controllerRelative, $controller);

// 2) Patch staff application review page to quietly show linked parcel agricultural status.
$appViewRelative = 'resources/views/staff/applications/show.blade.php';
$appView = read_file_required($appViewRelative);

if (! str_contains($appView, '$applicationAgriculturalStatusLabels')) {
    $search = <<<'BLADE'
        $landownerOptions = $landowners ?? \App\Models\Landowner::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(500)
            ->get();
BLADE;

    $replace = <<<'BLADE'
        $landownerOptions = $landowners ?? \App\Models\Landowner::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(500)
            ->get();

        $applicationAgriculturalStatusLabels = $application->applicationParcels
            ->pluck('parcel')
            ->filter()
            ->map(fn ($parcel) => $parcel->agricultural_status_label)
            ->filter()
            ->unique()
            ->values();
BLADE;

    $appView = replace_once_or_fail($appView, $search, $replace, $appViewRelative, 'application page php setup');
}

if (! str_contains($appView, 'Linked Parcel Agricultural Status')) {
    $search = <<<'BLADE'
                        <div class="summary-item">
                            <p class="summary-label">Municipality</p>
                            <p class="summary-value">{{ $application->municipality ?? '—' }}</p>
                        </div>
                    </div>
BLADE;

    $replace = <<<'BLADE'
                        <div class="summary-item">
                            <p class="summary-label">Municipality</p>
                            <p class="summary-value">{{ $application->municipality ?? '—' }}</p>
                        </div>

                        @if ($applicationAgriculturalStatusLabels->isNotEmpty())
                            <div class="summary-item">
                                <p class="summary-label">Linked Parcel Agricultural Status</p>
                                <div class="summary-value flex flex-wrap gap-2">
                                    @foreach ($applicationAgriculturalStatusLabels as $agriculturalStatusLabel)
                                        <span class="staff-badge staff-badge-slate">{{ $agriculturalStatusLabel }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
BLADE;

    $appView = replace_once_or_fail($appView, $search, $replace, $appViewRelative, 'application summary municipality block');
}

write_file_if_changed($appViewRelative, $appView);

// 3) Patch browser monitoring report page.
$reportViewRelative = 'resources/views/staff/reports/monitoring.blade.php';
$reportView = read_file_required($reportViewRelative);

if (! str_contains($reportView, '$agriculturalStatusRows')) {
    $search = <<<'BLADE'
        $statusDisplay = [
            'approved' => 'Approved Clearance',
            'not_approved' => 'Not Approved',
            'pending_review' => 'Pending Review',
            'draft' => 'Draft',
        ];
BLADE;

    $replace = <<<'BLADE'
        $statusDisplay = [
            'approved' => 'Approved Clearance',
            'not_approved' => 'Not Approved',
            'pending_review' => 'Pending Review',
            'draft' => 'Draft',
        ];

        $normalizedAgriculturalStatusBreakdown = collect($agriculturalStatusBreakdown ?? []);
        $agriculturalStatusOptions = $agriculturalStatusOptions ?? \App\Models\Parcel::agriculturalStatusOptions();
        $agriculturalStatusRows = collect($agriculturalStatusOptions)->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
            'count' => (int) ($normalizedAgriculturalStatusBreakdown[$key] ?? 0),
        ]);
BLADE;

    $reportView = replace_once_or_fail($reportView, $search, $replace, $reportViewRelative, 'monitoring report php setup');
}

if (! str_contains($reportView, 'Agricultural Status Summary')) {
    $search = <<<'BLADE'
            <article class="report-panel">
                <div class="report-panel-header">
                    <div>
                        <h2 class="report-panel-title">Municipality Breakdown</h2>
                        <p class="report-panel-subtitle">Applications grouped by recorded municipality.</p>
                    </div>
                    <span class="report-panel-count">Top locations</span>
                </div>

                <div class="report-list">
                    @forelse ($municipalityBreakdown as $row)
                        <div class="report-list-row">
                            <span class="report-list-label">{{ $row->municipality ?: 'Unspecified' }}</span>
                            <span class="staff-badge staff-badge-blue">{{ number_format($row->total) }}</span>
                        </div>
                    @empty
                        <div class="report-empty">No municipality data available.</div>
                    @endforelse
                </div>
            </article>
        </section>
BLADE;

    $replace = <<<'BLADE'
            <article class="report-panel">
                <div class="report-panel-header">
                    <div>
                        <h2 class="report-panel-title">Municipality Breakdown</h2>
                        <p class="report-panel-subtitle">Applications grouped by recorded municipality.</p>
                    </div>
                    <span class="report-panel-count">Top locations</span>
                </div>

                <div class="report-list">
                    @forelse ($municipalityBreakdown as $row)
                        <div class="report-list-row">
                            <span class="report-list-label">{{ $row->municipality ?: 'Unspecified' }}</span>
                            <span class="staff-badge staff-badge-blue">{{ number_format($row->total) }}</span>
                        </div>
                    @empty
                        <div class="report-empty">No municipality data available.</div>
                    @endforelse
                </div>
            </article>

            <article class="report-panel">
                <div class="report-panel-header">
                    <div>
                        <h2 class="report-panel-title">Agricultural Status Summary</h2>
                        <p class="report-panel-subtitle">Parcel classification context for DAR record monitoring.</p>
                    </div>
                    <span class="report-panel-count">Parcel records</span>
                </div>

                <div class="report-list">
                    @foreach ($agriculturalStatusRows as $row)
                        <div class="report-list-row">
                            <span class="report-list-label">{{ $row['label'] }}</span>
                            <span class="staff-badge staff-badge-slate">{{ number_format($row['count']) }}</span>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>
BLADE;

    $reportView = replace_once_or_fail($reportView, $search, $replace, $reportViewRelative, 'municipality report panel block');
}

write_file_if_changed($reportViewRelative, $reportView);

// 4) Patch printable monitoring report page.
$printViewRelative = 'resources/views/staff/reports/monitoring-print.blade.php';
$printView = read_file_required($printViewRelative);

if (! str_contains($printView, '$agriculturalStatusOptions = $agriculturalStatusOptions')) {
    $search = <<<'BLADE'
        $decisionLabel = fn ($value) => (string) $value === 'approved'
            ? 'Approved Clearance'
            : ucwords(str_replace('_', ' ', (string) $value));
    @endphp
BLADE;

    $replace = <<<'BLADE'
        $decisionLabel = fn ($value) => (string) $value === 'approved'
            ? 'Approved Clearance'
            : ucwords(str_replace('_', ' ', (string) $value));
        $agriculturalStatusOptions = $agriculturalStatusOptions ?? \App\Models\Parcel::agriculturalStatusOptions();
        $agriculturalStatusBreakdown = collect($agriculturalStatusBreakdown ?? []);
    @endphp
BLADE;

    $printView = replace_once_or_fail($printView, $search, $replace, $printViewRelative, 'print report php setup');
}

if (! str_contains($printView, '<h2 class="section-title">Agricultural Status Summary</h2>')) {
    $search = <<<'BLADE'
        <section class="section">
            <h2 class="section-title">Municipality Breakdown</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Municipality</th>
                        <th>Total Applications</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($municipalityBreakdown as $row)
                        <tr>
                            <td>{{ $row->municipality }}</td>
                            <td class="count-cell">{{ number_format($row->total) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">No municipality records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
BLADE;

    $replace = <<<'BLADE'
        <section class="section">
            <h2 class="section-title">Municipality Breakdown</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Municipality</th>
                        <th>Total Applications</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($municipalityBreakdown as $row)
                        <tr>
                            <td>{{ $row->municipality }}</td>
                            <td class="count-cell">{{ number_format($row->total) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">No municipality records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2 class="section-title">Agricultural Status Summary</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Agricultural Status</th>
                        <th>Total Parcel Records</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($agriculturalStatusOptions as $statusKey => $statusLabelText)
                        <tr>
                            <td>{{ $statusLabelText }}</td>
                            <td class="count-cell">{{ number_format((int) ($agriculturalStatusBreakdown[$statusKey] ?? 0)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
BLADE;

    $printView = replace_once_or_fail($printView, $search, $replace, $printViewRelative, 'print municipality section block');
}

write_file_if_changed($printViewRelative, $printView);

// 5) Add focused tests for this phase.
$testRelative = 'tests/Feature/ParcelAgriculturalStatusApplicationReportTest.php';
$testContents = <<<'PHP_CODE'
<?php

namespace Tests\Feature;

use App\Models\ApplicationParcel;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParcelAgriculturalStatusApplicationReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_application_review_displays_linked_parcel_agricultural_status(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'AGRI-APP-REVIEW-001',
            'transferor_name' => 'Review Transferor',
            'transferee_name' => 'Review Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_REVIEW,
            'encoded_by' => $staffUser->id,
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'AGRI-APP-PARCEL-001',
            'title_no' => 'TCT-AGRI-APP-001',
            'tax_decl_no' => 'TD-AGRI-APP-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'area_hectares' => 1.2500,
            'status' => 'active',
            'agricultural_status' => 'awarded_cloa',
            'remarks' => 'Application-linked parcel classification test.',
        ]);

        ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'title_no' => $parcel->title_no,
            'tax_decl_no' => $parcel->tax_decl_no,
            'area_hectares' => 1.2500,
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.applications.show', $application));

        $response->assertOk();
        $response->assertSee('Linked Parcel Agricultural Status');
        $response->assertSee('Awarded CLOA Land');
    }

    public function test_monitoring_reports_render_agricultural_status_summary(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        Parcel::create([
            'parcel_code' => 'AGRI-REPORT-PARCEL-001',
            'title_no' => 'TCT-AGRI-REPORT-001',
            'tax_decl_no' => 'TD-AGRI-REPORT-001',
            'municipality' => 'Bayawan City',
            'barangay' => 'Banga',
            'area_hectares' => 2.4000,
            'status' => 'active',
            'agricultural_status' => 'carp_covered',
            'remarks' => 'Report classification test.',
        ]);

        $browserResponse = $this->actingAs($staffUser)
            ->get(route('staff.reports.monitoring.index'));

        $browserResponse->assertOk();
        $browserResponse->assertSee('Agricultural Status Summary');
        $browserResponse->assertSee('CARP-Covered Land');

        $printResponse = $this->actingAs($staffUser)
            ->get(route('staff.reports.monitoring.print'));

        $printResponse->assertOk();
        $printResponse->assertSee('Agricultural Status Summary');
        $printResponse->assertSee('CARP-Covered Land');
    }
}
PHP_CODE;

write_file_if_changed($testRelative, $testContents);

echo "\nAgricultural status application/reports patch complete.\n";
