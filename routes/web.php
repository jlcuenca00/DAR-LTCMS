<?php

use App\Http\Controllers\Geodetic\GeodeticPortalController;
use App\Http\Controllers\Landowner\LandownerPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\ApplicationClearanceController;
use App\Http\Controllers\Staff\ApplicationDocumentController;
use App\Http\Controllers\Staff\ApplicationWorkflowController;
use App\Http\Controllers\Staff\LandTransferApplicationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
    Route::view('/staff/dashboard', 'dashboards.staff')
        ->middleware('role:staff')
        ->name('staff.dashboard');

    Route::view('/landowner/dashboard', 'dashboards.landowner')
        ->middleware('role:landowner')
        ->name('landowner.dashboard');

    Route::view('/geodetic/dashboard', 'dashboards.geodetic')
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
        Route::get('/parcels', [LandownerPortalController::class, 'parcels'])
            ->name('parcels.index');

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
    });

require __DIR__.'/auth.php';