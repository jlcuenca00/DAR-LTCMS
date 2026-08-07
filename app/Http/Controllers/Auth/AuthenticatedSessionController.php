<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
   public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();

    $request->session()->regenerate();

    $user = Auth::user();

    if ($user) {
        $user->forceFill(['last_login_at' => now()])->save();

        AuditLogger::record(
            'user_login',
            null,
            $user,
            [
                'user_id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'login_at' => $user->last_login_at?->toDateTimeString(),
            ],
            $user->id
        );
    }

    $request->session()->put(
        'auth_password_changed_at',
        $user?->password_changed_at?->format('Y-m-d H:i:s.u')
    );

    if ($user?->must_change_password) {
        return redirect()->route('password.required');
    }

    return match ($user?->role) {
        'staff' => redirect()->intended('/staff/dashboard'),
        'geodetic' => redirect()->intended('/geodetic/dashboard'),
        default => redirect()->intended('/landowner/dashboard'),
    };
}

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            AuditLogger::record(
                'user_logout',
                null,
                $user,
                [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role,
                ],
                $user->id
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
