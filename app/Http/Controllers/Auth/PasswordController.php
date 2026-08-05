<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();

        if (Hash::check($validated['password'], $user->password)) {
            return back()->withErrors([
                'password' => 'The new password must be different from your current password.',
            ], 'updatePassword');
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

        AuditLogger::record(
            'password_changed',
            null,
            $user,
            [
                'user_id' => $user->id,
                'username' => $user->username,
            ]
        );

        return back()->with('status', 'password-updated');
    }
}
