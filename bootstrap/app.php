<?php

use App\Http\Controllers\Staff\ProtectedStorageController;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['web', 'auth', 'role:staff'])
                ->get('/staff/protected-storage/{path}', ProtectedStorageController::class)
                ->where('path', '.*')
                ->name('staff.protected-storage.show');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $trustedProxies = (array) config('app.trusted_proxies', []);

        if ($trustedProxies !== []) {
            $proxyTargets = count($trustedProxies) === 1 && $trustedProxies[0] === '*'
                ? '*'
                : $trustedProxies;

            $middleware->trustProxies(
                at: $proxyTargets,
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO
            );
        }

        $trustedHosts = (array) config('app.trusted_hosts', []);

        if ($trustedHosts === []) {
            $host = parse_url((string) config('app.url'), PHP_URL_HOST);
            $trustedHosts = $host
                ? ['^'.preg_quote($host, '/').'$']
                : [];

            if (! app()->environment('production')) {
                $trustedHosts[] = '^localhost$';
                $trustedHosts[] = '^127\\.0\\.0\\.1$';
            }
        }

        $middleware->trustHosts(
            at: array_values(array_unique($trustedHosts)),
            subdomains: false
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