<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLandownerRegistrationApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== User::ROLE_LANDOWNER || $user->registration_status === User::REGISTRATION_APPROVED) {
            return $next($request);
        }

        if ($request->routeIs(
            'landowner.registration.pending',
            'logout',
            'profile.*',
            'password.*',
            'onboarding-tours.*'
        )) {
            return $next($request);
        }

        return redirect()->route('landowner.registration.pending');
    }
}
