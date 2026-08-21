<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    private const PROFILE_PHOTO_DISK = 'local';

    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'profilePhotoExists' => filled($user->profile_photo_path)
                && Storage::disk(self::PROFILE_PHOTO_DISK)->exists($user->profile_photo_path),
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
        $disk = Storage::disk(self::PROFILE_PHOTO_DISK);

        abort_if(
            blank($path) || ! $disk->exists($path),
            404
        );

        return $disk->response(
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
        $user = $request->user();
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

        $user->fill([
            'name' => $validated['name'],
            'email' => $newEmail,
        ]);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $photoChanged = false;

        if ($request->boolean('remove_profile_photo') && $user->profile_photo_path) {
            $this->deleteProfilePhoto($user->profile_photo_path);
            $user->profile_photo_path = null;
            $photoChanged = true;
        }

        if ($request->hasFile('profile_photo')) {
            $newPhotoPath = $request->file('profile_photo')->store('profile-photos', self::PROFILE_PHOTO_DISK);

            if ($user->profile_photo_path && $user->profile_photo_path !== $newPhotoPath) {
                $this->deleteProfilePhoto($user->profile_photo_path);
            } elseif ($oldPhotoPath && $oldPhotoPath !== $newPhotoPath) {
                $this->deleteProfilePhoto($oldPhotoPath);
            }

            $user->profile_photo_path = $newPhotoPath;
            $photoChanged = true;
        }

        $user->save();

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

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    private function deleteProfilePhoto(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        // Delete from both locations so legacy publicly stored photos cannot remain
        // addressable after a replacement/removal during the private-storage rollout.
        foreach ([self::PROFILE_PHOTO_DISK, 'public'] as $diskName) {
            $disk = Storage::disk($diskName);

            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }
}
