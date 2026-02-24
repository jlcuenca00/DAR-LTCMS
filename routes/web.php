<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::view('/staff/dashboard', 'dashboards.staff')->middleware('role:staff');
    Route::view('/landowner/dashboard', 'dashboards.landowner')->middleware('role:landowner');
    Route::view('/geodetic/dashboard', 'dashboards.geodetic')->middleware('role:geodetic');
});

require __DIR__.'/auth.php';
