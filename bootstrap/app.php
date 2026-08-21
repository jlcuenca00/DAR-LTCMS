<?php

use App\Http\Controllers\Staff\ProtectedStorageController;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['web', 'auth', 'role:staff'])->group(function (): void {
                Route::get('/staff/protected-storage/{path}', ProtectedStorageController::class)
                    ->where('path', '.*')
                    ->name('staff.protected-storage.show');

                // Compatibility for existing Staff reference-photo links. There is
                // no public/storage symlink; this route still performs record-level
                // registration checks in ProtectedStorageController.
                Route::get('/storage/{path}', ProtectedStorageController::class)
                    ->where('path', '.*');
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustHosts();
        $middleware->replace(
            \Illuminate\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\TrustHosts::class
        );
        $middleware->replace(
            \Illuminate\Http\Middleware\TrustProxies::class,
            \App\Http\Middleware\TrustProxies::class
        );

        $middleware->alias([
            'role' => App\Http\Middleware\RoleMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserIsActive::class,
            \App\Http\Middleware\EnsurePasswordIsCurrent::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\LockApplicationMutation::class,
            \App\Http\Middleware\EnsureReleaseDataIntegrity::class,
            \App\Http\Middleware\EnsureMutationAudited::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();