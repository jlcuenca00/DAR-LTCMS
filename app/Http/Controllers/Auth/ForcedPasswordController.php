<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcedPasswordController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return $this->redirectForRole($request->user());
        }

        return view('auth.force-password-change', [
            'passwordRecoveryVerified' => $request->session()->boolean('password_recovery_verified'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('forcedPassword', [
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();
        $passwordRecoveryVerified = $request->session()->boolean('password_recovery_verified');

        if (Hash::check($validated['password'], $user->password)) {
            return back()->withErrors([
                'password' => $passwordRecoveryVerified
                    ? 'Choose a new password that is different from your current password.'
                    : 'Choose a new password that is different from the temporary password.',
            ], 'forcedPassword');
        }

        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();

        $user->refresh();
        $request->session()->regenerate();
        $request->session()->put(
            'auth_password_changed_at',
            $user->password_changed_at?->format('Y-m-d H:i:s.u')
        );
        $request->session()->forget('password_recovery_verified');

        AuditLogger::record(
            $passwordRecoveryVerified ? 'password_recovery_completed' : 'password_changed_after_reset',
            null,
            $user,
            [
                'user_id' => $user->id,
                'username' => $user->username,
                'recovery_method' => $passwordRecoveryVerified ? 'email_otp' : 'forced_password_change',
            ]
        );

        return $this->redirectForRole($user)
            ->with('success', 'Your password has been changed successfully.');
    }

    private function redirectForRole(User $user): RedirectResponse
    {
        return match ($user->role) {
            User::ROLE_STAFF => redirect()->route('staff.dashboard'),
            User::ROLE_GEODETIC => redirect()->route('geodetic.dashboard'),
            default => redirect()->route('landowner.dashboard'),
        };
    }
}
