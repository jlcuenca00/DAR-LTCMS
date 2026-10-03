<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailAddedVerificationNotification;
use App\Notifications\LandownerRegistrationReceived;
use App\Services\AuditLogger;
use App\Services\GoogleIdentity;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

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
        if (is_string($request->input('username'))) {
            $request->merge(['username' => mb_strtolower(trim($request->input('username')))]);
        }

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

        try {
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
        } catch (UniqueConstraintViolationException $exception) {
            $field = User::query()->where('username', $validated['username'])->exists() ? 'username' : 'email';
            throw ValidationException::withMessages([
                $field => 'This '.$field.' is already registered. Use another value or sign in to your existing account.',
            ]);
        }

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

        if (filled($user->email)) {
            try {
                $user->notify(new EmailAddedVerificationNotification());

                AuditLogger::record(
                    'user_email_verification_email_sent',
                    null,
                    $user,
                    [
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'recipient_email' => $user->email,
                        'reason' => 'self_registration',
                        'verification_link_expires_in_hours' => 24,
                    ],
                    $user->id
                );

                $request->session()->flash(
                    'registration_email_status',
                    'A verification link was sent to your email. Verify it before using email password recovery.'
                );
            } catch (Throwable $exception) {
                report($exception);

                AuditLogger::record(
                    'user_email_verification_email_failed',
                    null,
                    $user,
                    [
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'recipient_email' => $user->email,
                        'reason' => 'self_registration',
                        'delivery_error_type' => $exception::class,
                    ],
                    $user->id
                );

                $request->session()->flash(
                    'registration_email_warning',
                    'Your registration was received, but the email verification message could not be sent. Your account is still waiting for DAR review.'
                );
            }
        }

        return redirect()->route('landowner.registration.pending');
    }

    public function storeGoogle(Request $request, GoogleIdentity $googleIdentity): RedirectResponse|Response
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
            if ($validated['intent'] === 'register') {
                return $this->accountExists();
            }

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

        if (! filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            throw ValidationException::withMessages([
                'google' => 'Google could not verify your email address. Please use manual registration.',
            ]);
        }

        $googleEmailAuthoritative = $this->googleEmailIsAuthoritative($payload, $email);

        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return $this->accountExists();
        }

        $name = trim((string) ($payload['name'] ?? 'Landowner'));
        $user = new User([
            'name' => $name !== '' ? $name : 'Landowner',
            'username' => $this->uniqueGoogleUsername($email, $name),
            'email' => $email,
            'google_id' => $googleId,
            'auth_provider' => 'google',
            'password' => Hash::make(Str::random(64)),
            'role' => User::ROLE_LANDOWNER,
            'registration_status' => User::REGISTRATION_PENDING,
            'is_active' => true,
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->email_verified_at = $googleEmailAuthoritative ? now() : null;
        $user->save();

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
                'google_email_authoritative' => $googleEmailAuthoritative,
                'google_hosted_domain' => $payload['hd'] ?? null,
            ],
            $user->id
        );

        try {
            $user->notify(new LandownerRegistrationReceived());
        } catch (Throwable $exception) {
            report($exception);
            $request->session()->flash('registration_email_warning',
                'Your registration was received, but the confirmation email could not be sent. Your account is still waiting for DAR review.');
        }

        if (! $googleEmailAuthoritative) {
            try {
                $user->notify(new EmailAddedVerificationNotification());

                AuditLogger::record(
                    'user_email_verification_email_sent',
                    null,
                    $user,
                    [
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'recipient_email' => $user->email,
                        'reason' => 'google_third_party_email',
                        'verification_link_expires_in_hours' => 24,
                    ],
                    $user->id
                );

                $request->session()->flash(
                    'registration_email_status',
                    'Google sign-in was verified, but this third-party email still needs a DAR-LTCMS verification link before it can be used for password recovery.'
                );
            } catch (Throwable $exception) {
                report($exception);

                AuditLogger::record(
                    'user_email_verification_email_failed',
                    null,
                    $user,
                    [
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'recipient_email' => $user->email,
                        'reason' => 'google_third_party_email',
                        'delivery_error_type' => $exception::class,
                    ],
                    $user->id
                );

                $request->session()->flash(
                    'registration_email_warning',
                    'Your registration was received, but the local email verification message could not be sent. The address remains unavailable for password recovery.'
                );
            }
        }

        return redirect()->route('landowner.registration.pending');
    }

    private function accountExists(): Response
    {
        return response()->view('auth.account-exists', [], 409)
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
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

    private function googleEmailIsAuthoritative(array $payload, string $email): bool
    {
        if (! filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $domain = mb_strtolower((string) Str::afterLast($email, '@'));

        if ($domain === 'gmail.com') {
            return true;
        }

        $hostedDomain = mb_strtolower(trim((string) ($payload['hd'] ?? '')));

        return $hostedDomain !== '' && hash_equals($hostedDomain, $domain);
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
