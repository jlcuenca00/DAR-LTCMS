<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\ApplicationDocumentController;
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

        Route::post(
            '/applications/{application}/documents/{requiredDocument}',
            [ApplicationDocumentController::class, 'store']
        )->name('staff.applications.documents.store');

    });

require __DIR__.'/auth.php';