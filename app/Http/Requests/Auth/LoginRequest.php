<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt([
            'username' => $this->input('username'),
            'password' => $this->input('password'),
        ], $this->boolean('remember'))) {
            $this->hitRateLimits();

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        $user = Auth::user();

        if (! $user?->is_active) {
            Auth::guard('web')->logout();
            $this->hitRateLimits();

            throw ValidationException::withMessages([
                'username' => 'This account is inactive. Please contact an authorized DAR staff account manager.',
            ]);
        }

        if (
            $user->must_change_password
            && $user->temporary_password_expires_at?->isPast()
        ) {
            Auth::guard('web')->logout();
            $this->hitRateLimits();

            throw ValidationException::withMessages([
                'username' => 'This temporary password has expired. Contact authorized DAR Staff for a new temporary password.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $identityLimited = RateLimiter::tooManyAttempts($this->throttleKey(), 5);
        $ipLimited = RateLimiter::tooManyAttempts($this->ipThrottleKey(), 30);

        if (! $identityLimited && ! $ipLimited) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(
            $identityLimited ? RateLimiter::availableIn($this->throttleKey()) : 0,
            $ipLimited ? RateLimiter::availableIn($this->ipThrottleKey()) : 0,
        );

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }

    public function ipThrottleKey(): string
    {
        return 'login-ip|'.$this->ip();
    }

    private function hitRateLimits(): void
    {
        RateLimiter::hit($this->throttleKey(), 60);
        RateLimiter::hit($this->ipThrottleKey(), 60);
    }
}
