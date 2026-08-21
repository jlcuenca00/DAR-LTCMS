<?php

namespace App\Http\Middleware;

use App\Models\LandTransferApplication;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LockApplicationMutation
{
    /**
     * Serialize every Staff mutation of an existing clearance application.
     *
     * The middleware runs after the session middleware, but before the Staff
     * route controller. It acquires a row-level lock and replaces the route-bound
     * application model with a fresh copy from inside the transaction. Existing
     * controller final-state checks therefore run against the exact locked state.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $route = $request->route();
        $routeName = $route?->getName();
        $routeApplication = $request->route('application');
        $user = $request->user();

        if (
            ! $user
            || $user->role !== 'staff'
            || ! is_string($routeName)
            || ! str_starts_with($routeName, 'staff.applications.')
            || $routeApplication === null
        ) {
            return $next($request);
        }

        $applicationId = $routeApplication instanceof LandTransferApplication
            ? $routeApplication->getKey()
            : $routeApplication;

        return DB::transaction(function () use ($request, $next, $applicationId) {
            $lockedApplication = LandTransferApplication::query()
                ->lockForUpdate()
                ->findOrFail($applicationId);

            $request->route()->setParameter('application', $lockedApplication);

            return $next($request);
        });
    }
}
