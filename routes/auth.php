<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\ForcedPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\OnboardingTourController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| DAR-LTCMS accounts are created and managed only by authorized DAR Staff.
| Public registration and email-based password recovery are intentionally
| unavailable because username is the account identifier.
|
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
});

Route::middleware('auth')->group(function () {
    Route::get('password/required', [ForcedPasswordController::class, 'edit'])
        ->name('password.required');
    Route::put('password/required', [ForcedPasswordController::class, 'update'])
        ->name('password.required.update');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])
        ->name('password.update');

    Route::get('onboarding-tours/{tourKey}', [OnboardingTourController::class, 'show'])
        ->name('onboarding-tours.show');
    Route::patch('onboarding-tours/{tourKey}', [OnboardingTourController::class, 'store'])
        ->name('onboarding-tours.store');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
