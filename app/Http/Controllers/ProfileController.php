<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Notifications\EmailAddedVerificationNotification;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProfileController extends Controller
{
    private const PROFILE_PHOTO_DISK = 'local';

    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'profilePhotoExists' => $this->ensurePrivateProfilePhoto($user),
        ]);
    }

    public function photo(Request $request, User $user): StreamedResponse
    {
        $viewer = $request->user();

        abort_unless(
            $viewer && ($viewer->id === $user->id || $viewer->isStaff()),
            403
        );

        $path = $user->profile_photo_path;

        abort_if(
            blank($path) || ! $this->ensurePrivateProfilePhoto($user),
            404
        );

        return Storage::disk(self::PROFILE_PHOTO_DISK)->response(
            $path,
            basename($path),
            [
                'Cache-Control' => 'private, max-age=300',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline'
        );
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $createdPaths = [];
        $verificationUser = null;
        try {
            $response = DB::transaction(function () use ($request, &$createdPaths, &$verificationUser) {
                return $this->updateLocked($request, $createdPaths, $verificationUser);
            });
        } catch (Throwable $exception) {
            foreach ($createdPaths as $path) {
                $this->deleteProfilePhotoQuietly($path);
            }
            throw $exception;
        }

        if ($verificationUser !== null) {
            $response->with('email_verification_status', $this->sendProfileVerification($verificationUser));
        }

        return $response;
    }

    private function updateLocked(ProfileUpdateRequest $request, array &$createdPaths, ?User &$verificationUser): RedirectResponse
    {
        $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
        $validated = $request->validated();
        $oldPhotoPath = $user->profile_photo_path;
        $oldProfile = [
            'name' => $user->name,
            'email' => $user->email,
            'profile_photo_path' => $oldPhotoPath,
        ];

        $newEmail = filled($validated['email'] ?? null)
            ? mb_strtolower(trim($validated['email']))
            : null;
        $emailChanged = mb_strtolower((string) $user->email) !== mb_strtolower((string) $newEmail);

        if ($emailChanged && ! $this->hasRecentPasswordConfirmation($request)) {
            $challengeKey = 'profile-email-change:'.$user->id.'|'.$request->ip();

            if (RateLimiter::tooManyAttempts($challengeKey, 5)) {
                $seconds = RateLimiter::availableIn($challengeKey);

                return back()
                    ->withInput($request->except(['current_password']))
                    ->withErrors([
                        'current_password' => "Too many incorrect password attempts. Try again in {$seconds} seconds.",
                    ]);
            }

            $currentPassword = (string) ($validated['current_password'] ?? '');

            if ($currentPassword === '' || ! Hash::check($currentPassword, $user->password)) {
                RateLimiter::hit($challengeKey, 60);

                return back()
                    ->withInput($request->except(['current_password']))
                    ->withErrors([
                        'current_password' => 'Confirm your current password before changing the recovery email. Google-only accounts without a local password should contact authorized DAR staff.',
                    ]);
            }

            RateLimiter::clear($challengeKey);
            $request->session()->put('auth.password_confirmed_at', time());
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $newEmail,
        ]);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $photoChanged = false;

        if ($request->boolean('remove_profile_photo') && $user->profile_photo_path) {
            $user->profile_photo_path = null;
            $photoChanged = true;
        }

        if ($request->hasFile('profile_photo')) {
            $newPhotoPath = $request->file('profile_photo')->store('profile-photos', self::PROFILE_PHOTO_DISK);

            $createdPaths[] = $newPhotoPath;

            $user->profile_photo_path = $newPhotoPath;
            $photoChanged = true;
        }

        $user->save();
        if ($oldPhotoPath && $oldPhotoPath !== $user->profile_photo_path) {
            DB::afterCommit(fn () => $this->deleteProfilePhotoQuietly($oldPhotoPath));
        }

        AuditLogger::record(
            'profile_updated',
            null,
            $user,
            [
                'user_id' => $user->id,
                'old_values' => $oldProfile,
                'new_values' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_photo_path' => $user->profile_photo_path,
                ],
                'profile_photo_changed' => $photoChanged,
                'email_verification_reset' => $emailChanged,
                'profile_photo_storage' => self::PROFILE_PHOTO_DISK,
            ]
        );

        if ($emailChanged && filled($user->email)) {
            $verificationUser = $user;
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    private function sendProfileVerification(User $user): string
    {
        try {
            $user->notify(new EmailAddedVerificationNotification());

            AuditLogger::record(
                'profile_email_verification_email_sent',
                null,
                $user,
                [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'recipient_email' => $user->email,
                    'verification_link_expires_in_hours' => 24,
                ]
            );

            $verificationMessage = 'Your email was changed. Verify the new address before it can be used for password recovery.';
        } catch (Throwable $exception) {
            report($exception);

            AuditLogger::record(
                'profile_email_verification_email_failed',
                null,
                $user,
                [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'recipient_email' => $user->email,
                    'delivery_error_type' => $exception::class,
                ]
            );

            $verificationMessage = 'Your email was changed, but the verification message could not be sent. The address remains unavailable for password recovery.';
        }

        return $verificationMessage;
    }

    private function hasRecentPasswordConfirmation(Request $request): bool
    {
        $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);
        $timeout = (int) config('auth.password_timeout', 10800);

        return $confirmedAt > 0 && (time() - $confirmedAt) < $timeout;
    }

    /**
     * Ensure a profile photo is stored on the private disk. Legacy photos that
     * were previously stored on the public disk are migrated only after an
     * authorization-checked request reaches this controller.
     */
    private function ensurePrivateProfilePhoto(User $user): bool
    {
        $path = $user->profile_photo_path;

        if (blank($path)) {
            return false;
        }

        $private = Storage::disk(self::PROFILE_PHOTO_DISK);
        $public = Storage::disk('public');

        if ($private->exists($path)) {
            if ($public->exists($path)) {
                $public->delete($path);
            }

            return true;
        }

        if (! $public->exists($path)) {
            return false;
        }

        $private->put($path, $public->get($path));

        if (! $private->exists($path)) {
            return false;
        }

        $public->delete($path);

        return true;
    }

    private function deleteProfilePhotoQuietly(?string $path): void
    {
        try {
            $this->deleteProfilePhoto($path);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function deleteProfilePhoto(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        foreach ([self::PROFILE_PHOTO_DISK, 'public'] as $diskName) {
            $disk = Storage::disk($diskName);

            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }
}
