<?php

namespace App\Services;

use App\Models\Landowner;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserAccountReviewService
{
    public function revision(User $user): string
    {
        // Activity timestamps and tour preferences must not invalidate a review.
        $state = $user->only([
            'id', 'name', 'username', 'email', 'email_verified_at',
            'role', 'is_active', 'registration_status', 'registration_notes',
            'registration_reviewed_at', 'registration_reviewed_by_user_id',
            'google_id', 'auth_provider', 'must_change_password',
            'password_changed_at', 'temporary_password_expires_at',
        ]);
        $state['credential_version'] = $user->getRawOriginal('password');
        $state['linked_landowner_id'] = $user->landowner()->value('id');

        return hash_hmac('sha256', json_encode($state, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    public function assertExpected(User $user, string $expected): void
    {
        if (! hash_equals($this->revision($user), $expected)) {
            throw ValidationException::withMessages([
                'expected_account_revision' => 'This account changed after you opened it. Reload and review the current account before saving.',
            ]);
        }
    }

    public function lockLandowners(User $user, ?int $selectedId): void
    {
        Landowner::query()->where(function ($query) use ($user, $selectedId) {
            $query->where('user_id', $user->id);
            if ($selectedId !== null) {
                $query->orWhere('id', $selectedId);
            }
        })->orderBy('id')->lockForUpdate()->get();
    }

    public function assertAvailable(?int $selectedId, ?User $user = null): void
    {
        if ($selectedId === null) {
            return;
        }

        $landowner = Landowner::query()->whereKey($selectedId)->lockForUpdate()->first();
        if (! $landowner || ($landowner->user_id !== null && (int) $landowner->user_id !== (int) $user?->id)) {
            throw ValidationException::withMessages([
                'landowner_id' => 'This landowner record is unavailable or already linked to another user account.',
            ]);
        }
    }

    public function sync(User $user, ?int $selectedId): void
    {
        $selectedId = $user->isLandowner() ? $selectedId : null;
        $this->assertAvailable($selectedId, $user);

        foreach ($user->landowner()->get() as $landowner) {
            if ((int) $landowner->id !== (int) $selectedId) {
                $landowner->user_id = null;
                $landowner->save();
            }
        }

        if ($selectedId !== null) {
            $landowner = Landowner::query()->findOrFail($selectedId);
            $landowner->user_id = $user->id;
            $landowner->save();
        }
        $user->unsetRelation('landowner');
    }
}
