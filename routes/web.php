<?php

use App\Http\Controllers\ProfileController;
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

    // Dashboards (Role Protected)
    Route::view('/staff/dashboard', 'dashboards.staff')->middleware('role:staff');
    Route::view('/landowner/dashboard', 'dashboards.landowner')->middleware('role:landowner');
    Route::view('/geodetic/dashboard', 'dashboards.geodetic')->middleware('role:geodetic');
});

/*
|--------------------------------------------------------------------------
| Staff-only Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:staff'])
    ->prefix('staff')
    ->group(function () {

        // Staff: Application Details (Checklist + Upload UI)
        Route::get(
            '/applications/{application}',
            [LandTransferApplicationController::class, 'show']
        )->name('staff.applications.show');

        // Staff: Upload a required document for an application
        Route::post(
            '/applications/{application}/documents/{requiredDocument}',
            [ApplicationDocumentController::class, 'store']
        )->name('staff.applications.documents.store');



Route::post('/applications/{application}/submit', [ApplicationWorkflowController::class, 'submit'])
    ->name('staff.applications.submit');

Route::post('/applications/{application}/approve', [ApplicationWorkflowController::class, 'approve'])
    ->name('staff.applications.approve');

Route::post('/applications/{application}/not-approved', [ApplicationWorkflowController::class, 'notApproved'])
    ->name('staff.applications.not_approved');

    });

require __DIR__.'/auth.php';