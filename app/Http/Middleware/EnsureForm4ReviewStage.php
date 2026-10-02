<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureForm4ReviewStage
{
    public function handle(Request $request, Closure $next): Response
    {
        $application = $request->route('application');

        if ($application && method_exists($application, 'isFinalized') && $application->isFinalized()) {
            return back()->with('error', 'LTC Form No. 4 review details are locked after a final clearance decision record.');
        }

        if (! $application || ! method_exists($application, 'canEditForm4') || ! $application->canEditForm4()) {
            return back()->with('error', 'LTC Form No. 4 may only be edited during LTID verification or the returned-to-Legal review stage.');
        }

        return $next($request);
    }
}
