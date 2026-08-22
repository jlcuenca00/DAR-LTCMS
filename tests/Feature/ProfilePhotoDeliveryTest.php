<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private const TINY_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    public function test_authenticated_user_can_view_own_private_profile_photo(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'profile_photo_path' => 'profile-photos/own-photo.png',
        ]);

        Storage::disk('local')->put(
            $user->profile_photo_path,
            base64_decode(self::TINY_PNG)
        );

        $this->actingAs($user)
            ->get(route('profile.photo', $user))
            ->assertOk();

        Storage::disk('public')->assertMissing($user->profile_photo_path);
    }

    public function test_account_menu_renders_private_profile_photo_route(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'profile_photo_path' => 'profile-photos/topbar-photo.png',
        ]);

        Storage::disk('local')->put(
            $user->profile_photo_path,
            base64_decode(self::TINY_PNG)
        );

        $html = Blade::render('<x-account-menu :user="$user" />', [
            'user' => $user,
        ]);

        $this->assertStringContainsString(
            'src="'.route('profile.photo', $user).'"',
            $html
        );
    }

    public function test_staff_can_view_another_users_private_profile_photo_for_user_management(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $landowner = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'profile_photo_path' => 'profile-photos/managed-user.png',
        ]);

        Storage::disk('local')->put(
            $landowner->profile_photo_path,
            base64_decode(self::TINY_PNG)
        );

        $this->actingAs($staff)
            ->get(route('profile.photo', $landowner))
            ->assertOk();
    }

    public function test_non_staff_user_cannot_view_another_users_profile_photo(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $viewer = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
        ]);

        $other = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'profile_photo_path' => 'profile-photos/other-user.png',
        ]);

        Storage::disk('local')->put(
            $other->profile_photo_path,
            base64_decode(self::TINY_PNG)
        );

        $this->actingAs($viewer)
            ->get(route('profile.photo', $other))
            ->assertForbidden();
    }

    public function test_legacy_public_profile_photo_is_migrated_to_private_storage_on_authorized_access(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'profile_photo_path' => 'profile-photos/legacy-photo.png',
        ]);

        Storage::disk('public')->put(
            $user->profile_photo_path,
            base64_decode(self::TINY_PNG)
        );

        Storage::disk('local')->assertMissing($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);

        $this->actingAs($user)
            ->get(route('profile.photo', $user))
            ->assertOk();

        Storage::disk('local')->assertExists($user->profile_photo_path);
        Storage::disk('public')->assertMissing($user->profile_photo_path);
    }

    public function test_missing_profile_photo_file_returns_404_and_profile_page_uses_fallback(): void
    {
        Storage::fake('local');
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
