<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
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

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
        ]);

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
            ]
        );

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
