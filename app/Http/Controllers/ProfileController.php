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
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'profilePhotoExists' => filled($user->profile_photo_path)
                && Storage::disk('public')->exists($user->profile_photo_path),
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
            blank($path) || ! Storage::disk('public')->exists($path),
            404
        );

        return Storage::disk('public')->response(
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
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
            $photoChanged = true;
        }

        if ($request->hasFile('profile_photo')) {
            $newPhotoPath = $request->file('profile_photo')->store('profile-photos', 'public');

            if ($user->profile_photo_path && $user->profile_photo_path !== $newPhotoPath) {
                Storage::disk('public')->delete($user->profile_photo_path);
            } elseif ($oldPhotoPath && $oldPhotoPath !== $newPhotoPath) {
                Storage::disk('public')->delete($oldPhotoPath);
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
            ]
        );

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
