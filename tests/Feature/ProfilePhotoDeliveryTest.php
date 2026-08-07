<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private const TINY_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    public function test_authenticated_user_can_view_own_profile_photo_without_public_storage_url(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'profile_photo_path' => 'profile-photos/own-photo.png',
        ]);

        Storage::disk('public')->put(
            $user->profile_photo_path,
            base64_decode(self::TINY_PNG)
        );

        $this->actingAs($user)
            ->get(route('profile.photo', $user))
            ->assertOk();
    }

    public function test_staff_can_view_another_users_profile_photo_for_user_management(): void
    {
        Storage::fake('public');

        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $landowner = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'profile_photo_path' => 'profile-photos/managed-user.png',
        ]);

        Storage::disk('public')->put(
            $landowner->profile_photo_path,
            base64_decode(self::TINY_PNG)
        );

        $this->actingAs($staff)
            ->get(route('profile.photo', $landowner))
            ->assertOk();
    }

    public function test_non_staff_user_cannot_view_another_users_profile_photo(): void
    {
        Storage::fake('public');

        $viewer = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
        ]);

        $other = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'profile_photo_path' => 'profile-photos/other-user.png',
        ]);

        Storage::disk('public')->put(
            $other->profile_photo_path,
            base64_decode(self::TINY_PNG)
        );

        $this->actingAs($viewer)
            ->get(route('profile.photo', $other))
            ->assertForbidden();
    }

    public function test_missing_profile_photo_file_returns_404_and_profile_page_uses_fallback(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'profile_photo_path' => 'profile-photos/missing.png',
        ]);

        $this->actingAs($user)
            ->get(route('profile.photo', $user))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('No photo')
            ->assertSee('previously stored photo file is unavailable')
            ->assertDontSee('/storage/profile-photos/missing.png', false);
    }
}