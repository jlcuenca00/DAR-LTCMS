<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsCurrent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $currentVersion = $this->passwordVersion($user->password_changed_at);
        $sessionVersion = $request->session()->get('auth_password_changed_at');

        if ($currentVersion !== null && $sessionVersion !== $currentVersion) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('status', 'Your password was changed. Sign in again using the current password.');
        }

        if (
            $user->must_change_password
            && ! $request->routeIs('password.required', 'password.required.update', 'logout')
        ) {
            return redirect()->route('password.required');
        }

        return $next($request);
    }

    private function passwordVersion($changedAt): ?string
    {
        return $changedAt?->format('Y-m-d H:i:s.u');
    }
}
