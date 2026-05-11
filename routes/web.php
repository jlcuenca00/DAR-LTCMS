<?php

use App\Http\Controllers\Geodetic\GeodeticPortalController;
use App\Http\Controllers\Geodetic\ParcelMapController as GeodeticParcelMapController;
use App\Http\Controllers\Landowner\LandownerPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\ApplicationClearanceController;
use App\Http\Controllers\Staff\ApplicationDocumentController;
use App\Http\Controllers\Staff\ApplicationWorkflowController;
use App\Http\Controllers\Staff\LandTransferApplicationController;
use App\Http\Controllers\Staff\MonitoringReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\LegacyRecordController;
use App\Http\Controllers\Staff\AuditLogController;
use App\Http\Controllers\Staff\UserManagementController;
use App\Http\Controllers\Staff\RecordSearchController;
use App\Http\Controllers\Staff\ParcelMapController;
use App\Http\Controllers\Landowner\ParcelMapController as LandownerParcelMapController;
use App\Http\Controllers\Staff\SourceRecordPackageController;
use App\Http\Controllers\Staff\SourceRecordPackageImportController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Landowner\LandownerDashboardController;
use App\Http\Controllers\Geodetic\GeodeticDashboardController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboards
   Route::get('/staff/dashboard', StaffDashboardController::class)
    ->middleware('role:staff')
    ->name('staff.dashboard');

    Route::get('/landowner/dashboard', LandownerDashboardController::class)
    ->middleware('role:landowner')
    ->name('landowner.dashboard');

    Route::get('/geodetic/dashboard', GeodeticDashboardController::class)
    ->middleware('role:geodetic')
    ->name('geodetic.dashboard');
});

/*
|--------------------------------------------------------------------------
| Staff-only Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:staff'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {

        Route::get('/legacy-records', [LegacyRecordController::class, 'index'])
    ->name('legacy-records.index');

Route::get('/legacy-records/create', [LegacyRecordController::class, 'create'])
    ->name('legacy-records.create');

Route::post('/legacy-records', [LegacyRecordController::class, 'store'])
    ->name('legacy-records.store');

Route::get('/legacy-records/{legacyRecord}', [LegacyRecordController::class, 'show'])
    ->name('legacy-records.show');

Route::post('/legacy-records/{legacyRecord}/link-parcel', [LegacyRecordController::class, 'linkParcel'])
    ->name('legacy-records.link-parcel');

Route::post('/legacy-records/{legacyRecord}/create-parcel', [LegacyRecordController::class, 'createParcel'])
    ->name('legacy-records.create-parcel');

    Route::get('/source-record-packages/create', [SourceRecordPackageController::class, 'create'])
    ->name('source-record-packages.create');

Route::post('/source-record-packages', [SourceRecordPackageController::class, 'store'])
    ->name('source-record-packages.store');

Route::get('/source-record-packages/{sourceRecordPackage}', [SourceRecordPackageController::class, 'show'])
    ->name('source-record-packages.show');

Route::post('/source-record-packages/{sourceRecordPackage}/link-parcel', [SourceRecordPackageController::class, 'linkParcel'])
    ->name('source-record-packages.link-parcel');

Route::post('/source-record-packages/{sourceRecordPackage}/create-parcel', [SourceRecordPackageController::class, 'createParcel'])
    ->name('source-record-packages.create-parcel');

    Route::get('/source-record-package-imports/create', [SourceRecordPackageImportController::class, 'create'])
    ->name('source-record-package-imports.create');

Route::get('/source-record-package-imports/template', [SourceRecordPackageImportController::class, 'template'])
    ->name('source-record-package-imports.template');

Route::post('/source-record-package-imports/preview', [SourceRecordPackageImportController::class, 'preview'])
    ->name('source-record-package-imports.preview.store');

Route::get('/source-record-package-imports/{batch}/preview', [SourceRecordPackageImportController::class, 'showPreview'])
    ->name('source-record-package-imports.preview');

Route::post('/source-record-package-imports/{batch}/commit', [SourceRecordPackageImportController::class, 'commit'])
    ->name('source-record-package-imports.commit');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');

        Route::resource('users', UserManagementController::class)
            ->only(['index', 'create', 'store', 'edit', 'update']);

        Route::get('/records/landowners', [RecordSearchController::class, 'landowners'])
            ->name('records.landowners.index');

        Route::get('/records/parcels', [RecordSearchController::class, 'parcels'])
            ->name('records.parcels.index');

        Route::get('/records/parcels/{parcel}', [RecordSearchController::class, 'showParcel'])
            ->name('records.parcels.show');

        Route::get('/reports/monitoring', [MonitoringReportController::class, 'index'])
            ->name('reports.monitoring.index');
        
        Route::get('/reports/monitoring/print', [MonitoringReportController::class, 'print'])
            ->name('reports.monitoring.print');

        Route::get('/parcel-map', [ParcelMapController::class, 'index'])
            ->name('parcel-map.index');
        
        Route::get('/applications', [LandTransferApplicationController::class, 'index'])
    ->name('applications.index');

Route::get('/applications/create', [LandTransferApplicationController::class, 'create'])
    ->name('applications.create');

Route::post('/applications', [LandTransferApplicationController::class, 'store'])
    ->name('applications.store');
    
Route::get('/applications/{application}', [LandTransferApplicationController::class, 'show'])
    ->name('applications.show');

        Route::post('/applications/{application}/documents/{requiredDocument}', [ApplicationDocumentController::class, 'store'])
            ->name('applications.documents.store');

        Route::delete('/applications/{application}/documents/{requiredDocument}', [ApplicationDocumentController::class, 'destroy'])
            ->name('applications.documents.destroy');

        Route::post('/applications/{application}/submit', [ApplicationWorkflowController::class, 'submit'])
            ->name('applications.submit');

        Route::post('/applications/{application}/approve', [ApplicationWorkflowController::class, 'approve'])
            ->name('applications.approve');

        Route::post('/applications/{application}/not-approved', [ApplicationWorkflowController::class, 'notApproved'])
            ->name('applications.not_approved');

        Route::get('/applications/{application}/clearance', [ApplicationClearanceController::class, 'show'])
            ->name('applications.clearance.show');

        Route::get('/applications/{application}/clearance/pdf', [ApplicationClearanceController::class, 'pdf'])
            ->name('applications.clearance.pdf');
    });

/*
|--------------------------------------------------------------------------
| Landowner-only Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:landowner'])
    ->prefix('landowner')
    ->name('landowner.')
    ->group(function () {
        Route::get('/parcel-map', [LandownerParcelMapController::class, 'index'])
            ->name('parcel-map.index');

        Route::get('/parcels', [LandownerPortalController::class, 'parcels'])
            ->name('parcels.index');

        Route::get('/parcels/{parcel}', [LandownerParcelMapController::class, 'show'])
            ->name('parcels.show');

        Route::get('/applications', [LandownerPortalController::class, 'applications'])
            ->name('applications.index');
    });

/*
|--------------------------------------------------------------------------
| Geodetic-only Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:geodetic'])
    ->prefix('geodetic')
    ->name('geodetic.')
    ->group(function () {
        Route::get('/parcels', [GeodeticPortalController::class, 'parcels'])
            ->name('parcels.index');

        Route::get('/applications', [GeodeticPortalController::class, 'applications'])
            ->name('applications.index');
        Route::get('/parcel-map', [GeodeticParcelMapController::class, 'index'])
            ->name('parcel-map.index');

        Route::get('/parcels/{parcel}', [GeodeticParcelMapController::class, 'show'])
            ->name('parcels.show');
            
    });

require __DIR__.'/auth.php';