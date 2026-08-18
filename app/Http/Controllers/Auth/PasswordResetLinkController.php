<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordRecoveryCodeNotification;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    private const OTP_TTL_SECONDS = 600;
    private const OTP_RESEND_COOLDOWN_SECONDS = 60;
    private const MAX_EMAIL_ATTEMPTS = 5;
    private const MAX_CODE_ATTEMPTS = 5;

    public function create(Request $request): View
    {
        return view('auth.forgot-password', [
            'recovery' => $request->session()->get('password_recovery', []),
        ]);
    }

    public function identify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100'],
        ]);

        $user = User::query()
            ->where('username', $validated['username'])
            ->first();

        if (! $user) {
            return back()
                ->withInput()
                ->withErrors([
                    'username' => 'No account was found for that username. Verify the username or approach authorized DAR Staff for assistance.',
                ]);
        }

        if (! $user->is_active) {
            $request->session()->forget('password_recovery');

            return back()->with(
                'status',
                'This account is currently inactive. Please approach authorized DAR Staff at the DAR Negros Oriental Provincial Office for account assistance.'
            );
        }

        if (! $this->hasRecoverableEmail($user)) {
            $request->session()->forget('password_recovery');

            AuditLogger::record(
                'password_recovery_requested_without_email',
                null,
                $user,
                [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'recovery_available' => false,
                ]
            );

            return back()->with(
                'status',
                'No email address is registered with this account. Please approach authorized DAR Staff at the DAR Negros Oriental Provincial Office for password assistance.'
            );
        }

        $recovery = [
            'step' => 'confirm_email',
            'user_id' => $user->id,
            'username' => $user->username,
            'masked_email' => $this->maskEmail($user->email),
            'email_attempts' => 0,
            'code_attempts' => 0,
        ];

        $request->session()->put('password_recovery', $recovery);

        AuditLogger::record(
            'password_recovery_requested',
            null,
            $user,
            [
                'user_id' => $user->id,
                'username' => $user->username,
                'recovery_available' => true,
            ]
        );

        return redirect()->route('password.request');
    }

    public function confirmEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $recovery = $this->requireRecoveryState($request, 'confirm_email');
        $user = User::find($recovery['user_id']);

        if (! $user || ! $user->is_active || ! $this->hasRecoverableEmail($user)) {
            $request->session()->forget('password_recovery');

            return redirect()
                ->route('password.request')
                ->with('status', 'Account recovery is unavailable. Please approach authorized DAR Staff for assistance.');
        }

        $attempts = (int) ($recovery['email_attempts'] ?? 0) + 1;
        $recovery['email_attempts'] = $attempts;
        $request->session()->put('password_recovery', $recovery);

        if ($attempts > self::MAX_EMAIL_ATTEMPTS) {
            $request->session()->forget('password_recovery');

            return redirect()
                ->route('password.request')
                ->withErrors([
                    'username' => 'Too many incorrect email confirmation attempts. Start again or approach authorized DAR Staff for assistance.',
                ]);
        }

        if (mb_strtolower(trim($validated['email'])) !== mb_strtolower(trim((string) $user->email))) {
            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'The email address does not match the registered email for this account.',
                ]);
        }

        $recovery['email_attempts'] = 0;
        $recovery['email_confirmed_at'] = time();
        $request->session()->put('password_recovery', $recovery);

        AuditLogger::record(
            'password_recovery_email_confirmed',
            null,
            $user,
            [
                'user_id' => $user->id,
                'username' => $user->username,
            ]
        );

        if (! $this->sendRecoveryCode($request, $user)) {
            return back()->withErrors([
                'email' => 'The verification email could not be sent. Please try again later or approach authorized DAR Staff for assistance.',
            ]);
        }

        return redirect()
            ->route('password.request')
            ->with('status', 'Email confirmed. A 6-digit verification code has been sent to your registered email address.');
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $recovery = $this->requireRecoveryState($request, 'otp');
        $user = User::find($recovery['user_id']);

        if (! $user || ! $user->is_active || ! $this->hasRecoverableEmail($user)) {
            $request->session()->forget('password_recovery');

            return redirect()
                ->route('password.request')
                ->with('status', 'Account recovery is unavailable. Please approach authorized DAR Staff for assistance.');
        }

        $sentAt = (int) ($recovery['otp_sent_at'] ?? 0);
        $secondsRemaining = self::OTP_RESEND_COOLDOWN_SECONDS - (time() - $sentAt);

        if ($sentAt > 0 && $secondsRemaining > 0) {
            return back()->withErrors([
                'code' => "Please wait {$secondsRemaining} seconds before requesting another code.",
            ]);
        }

        if (! $this->sendRecoveryCode($request, $user)) {
            return back()->withErrors([
                'code' => 'The verification email could not be sent. Please try again later or approach authorized DAR Staff for assistance.',
            ]);
        }

        return redirect()
            ->route('password.request')
            ->with('status', 'A new 6-digit verification code has been sent. The previous code is no longer valid.');
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'regex:/^\d{6}$/'],
        ]);

        $recovery = $this->requireRecoveryState($request, 'otp');
        $user = User::find($recovery['user_id']);

        if (! $user || ! $user->is_active) {
            $request->session()->forget('password_recovery');

            return redirect()
                ->route('password.request')
                ->with('status', 'Account recovery is unavailable. Please approach authorized DAR Staff for assistance.');
        }

        if (empty($recovery['otp_hash']) || time() > (int) ($recovery['otp_expires_at'] ?? 0)) {
            $recovery['otp_hash'] = null;
            $recovery['otp_expires_at'] = null;
            $recovery['code_attempts'] = 0;
            $request->session()->put('password_recovery', $recovery);

            return back()->withErrors([
                'code' => 'This verification code has expired. Request a new code to continue.',
            ]);
        }

        $attempts = (int) ($recovery['code_attempts'] ?? 0) + 1;
        $recovery['code_attempts'] = $attempts;
        $request->session()->put('password_recovery', $recovery);

        if ($attempts > self::MAX_CODE_ATTEMPTS) {
            $request->session()->forget('password_recovery');

            return redirect()
                ->route('password.request')
                ->withErrors([
                    'username' => 'Too many incorrect verification-code attempts. Start the recovery process again.',
                ]);
        }

        if (! Hash::check($validated['code'], $recovery['otp_hash'])) {
            return back()->withErrors([
                'code' => 'The verification code is incorrect.',
            ]);
        }

        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'must_change_password' => true,
        ])->save();

        AuditLogger::record(
            'password_recovery_code_verified',
            null,
            $user,
            [
                'user_id' => $user->id,
                'username' => $user->username,
                'recovery_method' => 'email_otp',
            ]
        );

        $request->session()->forget('password_recovery');

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put(
            'auth_password_changed_at',
            $user->password_changed_at?->format('Y-m-d H:i:s.u')
        );
        $request->session()->put('password_recovery_verified', true);

        return redirect()->route('password.required');
    }

    public function restart(Request $request): RedirectResponse
    {
        $request->session()->forget('password_recovery');

        return redirect()->route('password.request');
    }

    private function sendRecoveryCode(Request $request, User $user): bool
    {
        if (
            app()->environment('production')
            && in_array(config('mail.default'), ['log', 'array'], true)
        ) {
            AuditLogger::record(
                'password_recovery_mail_unavailable',
                null,
                $user,
                [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'configured_mailer' => config('mail.default'),
                ]
            );

            return false;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $recovery = $request->session()->get('password_recovery', []);

        try {
            $user->notify(new PasswordRecoveryCodeNotification($code));
        } catch (Throwable $exception) {
            report($exception);

            AuditLogger::record(
                'password_recovery_code_delivery_failed',
                null,
                $user,
                [
                    'user_id' => $user->id,
                    'username' => $user->username,
                ]
            );

            return false;
        }

        $recovery['step'] = 'otp';
        $recovery['otp_hash'] = Hash::make($code);
        $recovery['otp_expires_at'] = time() + self::OTP_TTL_SECONDS;
        $recovery['otp_sent_at'] = time();
        $recovery['code_attempts'] = 0;
        $request->session()->put('password_recovery', $recovery);

        AuditLogger::record(
            'password_recovery_code_sent',
            null,
            $user,
            [
                'user_id' => $user->id,
                'username' => $user->username,
                'expires_in_minutes' => 10,
            ]
        );

        return true;
    }

    private function requireRecoveryState(Request $request, string $expectedStep): array
    {
        $recovery = $request->session()->get('password_recovery', []);

        if (($recovery['step'] ?? null) !== $expectedStep || empty($recovery['user_id'])) {
            throw ValidationException::withMessages([
                'username' => 'The password recovery session has expired or is incomplete. Start again.',
            ]);
        }

        return $recovery;
    }

    private function hasRecoverableEmail(User $user): bool
    {
        if (blank($user->email)) {
            return false;
        }

        return ! str_ends_with(mb_strtolower($user->email), '@dar-ltcms.local');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $length = mb_strlen($local);

        if ($length <= 1) {
            $maskedLocal = '*';
        } else {
            $maskedLocal = '';

            for ($index = 0; $index < $length; $index++) {
                $character = mb_substr($local, $index, 1);

                if ($character === '.') {
                    $maskedLocal .= '.';
                } elseif ($index === 0 || $index === $length - 1) {
                    $maskedLocal .= $character;
                } else {
                    $maskedLocal .= '*';
                }
            }
        }

        return $maskedLocal . '@' . $domain;
    }
}
