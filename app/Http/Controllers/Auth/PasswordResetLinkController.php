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

        $recoverableUser = $user && $user->is_active && $this->hasRecoverableEmail($user)
            ? $user
            : null;

        $request->session()->put('password_recovery', [
            'step' => 'confirm_email',
            'user_id' => $recoverableUser?->id,
            'username' => $validated['username'],
            'email_attempts' => 0,
            'code_attempts' => 0,
        ]);

        // Keep the public response deliberately generic. Whether the username
        // exists, is inactive, or has a recovery email is not disclosed.
        if ($user) {
            AuditLogger::record(
                'password_recovery_requested',
                null,
                $user,
                [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'recovery_available' => (bool) $recoverableUser,
                ]
            );
        }

        return redirect()
            ->route('password.request')
            ->with('status', 'Continue by entering the registered recovery email for this username. If email recovery is unavailable, no verification code will be sent; contact authorized DAR Staff for assistance.');
    }

    public function confirmEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $recovery = $this->requireRecoveryState($request, 'confirm_email', allowMissingUser: true);
        $user = ! empty($recovery['user_id']) ? User::find($recovery['user_id']) : null;

        $attempts = (int) ($recovery['email_attempts'] ?? 0) + 1;
        $recovery['email_attempts'] = $attempts;
        $request->session()->put('password_recovery', $recovery);

        if ($attempts > self::MAX_EMAIL_ATTEMPTS) {
            $request->session()->forget('password_recovery');

            return redirect()
                ->route('password.request')
                ->withErrors([
                    'username' => 'Too many recovery verification attempts. Start again or contact authorized DAR Staff for assistance.',
                ]);
        }

        $emailMatches = $user
            && $user->is_active
            && $this->hasRecoverableEmail($user)
            && hash_equals(
                mb_strtolower(trim((string) $user->email)),
                mb_strtolower(trim($validated['email']))
            );

        if (! $emailMatches) {
            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'The recovery details could not be verified. Check the username and email, or contact authorized DAR Staff for assistance.',
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
                'email' => 'The verification email could not be sent. Please try again later or contact authorized DAR Staff for assistance.',
            ]);
        }

        return redirect()
            ->route('password.request')
            ->with('status', 'Recovery details confirmed. A 6-digit verification code has been sent to the registered recovery email.');
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $recovery = $this->requireRecoveryState($request, 'otp');
        $user = User::find($recovery['user_id']);

        if (! $user || ! $user->is_active || ! $this->hasRecoverableEmail($user)) {
            $request->session()->forget('password_recovery');

            return redirect()
                ->route('password.request')
                ->with('status', 'Account recovery could not be completed. Please start again or contact authorized DAR Staff for assistance.');
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
                'code' => 'The verification email could not be sent. Please try again later or contact authorized DAR Staff for assistance.',
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
                ->with('status', 'Account recovery could not be completed. Please start again or contact authorized DAR Staff for assistance.');
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

    private function requireRecoveryState(Request $request, string $expectedStep, bool $allowMissingUser = false): array
    {
        $recovery = $request->session()->get('password_recovery', []);

        $invalidState = ($recovery['step'] ?? null) !== $expectedStep
            || empty($recovery['username'])
            || (! $allowMissingUser && empty($recovery['user_id']));

        if ($invalidState) {
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
}
