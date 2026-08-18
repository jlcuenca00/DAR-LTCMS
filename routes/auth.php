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
| Username remains the account identifier. Accounts with a registered email
| may use email-confirmed OTP password recovery; accounts without email use
| the existing DAR Staff-assisted reset process.
|
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password/identify', [PasswordResetLinkController::class, 'identify'])
        ->middleware('throttle:5,1')
        ->name('password.recovery.identify');

    Route::post('forgot-password/confirm-email', [PasswordResetLinkController::class, 'confirmEmail'])
        ->middleware('throttle:10,1')
        ->name('password.recovery.confirm-email');

    Route::post('forgot-password/verify-code', [PasswordResetLinkController::class, 'verifyCode'])
        ->middleware('throttle:10,10')
        ->name('password.recovery.verify-code');

    Route::post('forgot-password/resend-code', [PasswordResetLinkController::class, 'resendCode'])
        ->middleware('throttle:5,15')
        ->name('password.recovery.resend-code');

    Route::post('forgot-password/restart', [PasswordResetLinkController::class, 'restart'])
        ->name('password.recovery.restart');
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
