<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\GoogleIdentity;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'googleClientId' => config('services.google.client_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash:ascii', 'max:100', 'unique:users,username'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'privacy_consent' => ['accepted'],
        ]);

        $email = filled($validated['email'] ?? null)
            ? mb_strtolower(trim($validated['email']))
            : null;

        $user = User::create([
            'name' => trim($validated['name']),
            'username' => mb_strtolower(trim($validated['username'])),
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_LANDOWNER,
            'auth_provider' => 'local',
            'registration_status' => User::REGISTRATION_PENDING,
            'is_active' => true,
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        event(new Registered($user));

        $this->login($request, $user);

        AuditLogger::record(
            'landowner_self_registered',
            null,
            $user,
            [
                'user_id' => $user->id,
                'username' => $user->username,
                'auth_provider' => 'local',
                'registration_status' => $user->registration_status,
            ],
            $user->id
        );

        return redirect()->route('landowner.registration.pending');
    }

    public function storeGoogle(Request $request, GoogleIdentity $googleIdentity): RedirectResponse
    {
        $validated = $request->validate([
            'credential' => ['required', 'string', 'max:10000'],
            'intent' => ['required', 'string', 'in:login,register'],
            'privacy_consent' => ['nullable'],
        ]);

        $payload = $googleIdentity->verify($validated['credential']);

        if (! $payload) {
            throw ValidationException::withMessages([
                'google' => 'Google sign-in could not be verified. Please try again.',
            ]);
        }

        $googleId = (string) $payload['sub'];
        $existingGoogleUser = User::query()->where('google_id', $googleId)->first();

        if ($existingGoogleUser) {
            if (! $existingGoogleUser->is_active) {
                throw ValidationException::withMessages([
                    'google' => 'This account is inactive. Please contact authorized DAR staff.',
                ]);
            }

            $this->login($request, $existingGoogleUser);

            AuditLogger::record(
                'user_login',
                null,
                $existingGoogleUser,
                [
                    'user_id' => $existingGoogleUser->id,
                    'username' => $existingGoogleUser->username,
                    'role' => $existingGoogleUser->role,
                    'auth_provider' => 'google',
                ],
                $existingGoogleUser->id
            );

            return redirect()->intended(route('dashboard', absolute: false));
        }

        if ($validated['intent'] !== 'register') {
            return redirect()->route('register')->withErrors([
                'google' => 'No DAR-LTCMS account is linked to that Google account yet. Register as a landowner first.',
            ]);
        }

        $request->validate([
            'privacy_consent' => ['accepted'],
        ]);

        $email = isset($payload['email']) && filter_var($payload['email'], FILTER_VALIDATE_EMAIL)
            ? mb_strtolower(trim((string) $payload['email']))
            : null;

        if (! $email) {
            throw ValidationException::withMessages([
                'google' => 'Google did not provide a usable email address. Please use manual registration.',
            ]);
        }

        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages([
                'google' => 'That email already belongs to a DAR-LTCMS account. Sign in with its username, or ask DAR staff for help linking Google safely.',
            ]);
        }

        $name = trim((string) ($payload['name'] ?? 'Landowner'));
        $user = User::create([
            'name' => $name !== '' ? $name : 'Landowner',
            'username' => $this->uniqueGoogleUsername($email, $name),
            'email' => $email,
            'email_verified_at' => filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN) ? now() : null,
            'google_id' => $googleId,
            'auth_provider' => 'google',
            'password' => Hash::make(Str::random(64)),
            'role' => User::ROLE_LANDOWNER,
            'registration_status' => User::REGISTRATION_PENDING,
            'is_active' => true,
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        event(new Registered($user));

        $this->login($request, $user);

        AuditLogger::record(
            'landowner_self_registered',
            null,
            $user,
            [
                'user_id' => $user->id,
                'username' => $user->username,
                'auth_provider' => 'google',
                'registration_status' => $user->registration_status,
            ],
            $user->id
        );

        return redirect()->route('landowner.registration.pending');
    }

    public function pending(Request $request): View
    {
        return view('auth.landowner-pending', [
            'user' => $request->user(),
        ]);
    }

    private function login(Request $request, User $user): void
    {
        $user->forceFill(['last_login_at' => now()])->save();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put(
            'auth_password_changed_at',
            $user->password_changed_at?->format('Y-m-d H:i:s.u')
        );
    }

    private function uniqueGoogleUsername(string $email, string $name): string
    {
        $base = Str::of(Str::before($email, '@'))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9_-]+/', '_')
            ->trim('_-')
            ->limit(80, '')
            ->toString();

        if ($base === '') {
            $base = Str::of($name)
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9_-]+/', '_')
                ->trim('_-')
                ->limit(80, '')
                ->toString();
        }

        $base = $base !== '' ? $base : 'landowner';
        $username = $base;

        while (User::query()->where('username', $username)->exists()) {
            $username = $base.'_'.Str::lower(Str::random(6));
        }

        return $username;
    }
}
