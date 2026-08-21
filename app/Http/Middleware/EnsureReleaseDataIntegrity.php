<?php

namespace App\Http\Middleware;

use App\Models\LandTransferApplication;
use App\Services\ApplicationPartyShareIntegrityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReleaseDataIntegrity
{
    public function handle(Request $request, Closure $next): Response
    {
        $application = $request->route('application');

        if (! $application instanceof LandTransferApplication) {
            return $next($request);
        }

        $requiresCheck = $request->routeIs('staff.applications.approve')
            || ($request->routeIs('staff.applications.submit')
                && $application->status === LandTransferApplication::STATUS_ENDORSED_PARPO);

        if (! $requiresCheck) {
            return $next($request);
        }

        $integrity = app(ApplicationPartyShareIntegrityService::class)->inspect($application);

        if (! $integrity['valid']) {
            return back()->withErrors([
                'validation' => 'Resolve the application data-integrity issues before preparing or releasing this clearance.',
                'transferee_shares' => implode(' ', $integrity['issues']),
            ]);
        }

        return $next($request);
    }
}
