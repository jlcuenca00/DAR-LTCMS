<?php

namespace Tests\Feature;

use App\Models\Landowner;
use App\Models\User;
use App\Services\UserAccountReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserAccountReviewIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        Notification::fake();
        return User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true]);
    }

    private function payload(User $user, array $overrides = []): array
    {
        return array_replace([
            'expected_account_revision' => app(UserAccountReviewService::class)->revision($user->fresh()),
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active ? '1' : '0',
            'registration_status' => $user->registration_status,
            'landowner_id' => $user->landowner()->value('id'),
        ], $overrides);
    }

    private function landowner(?User $user = null): Landowner
    {
        return Landowner::create([
            'first_name' => 'Linked', 'last_name' => 'Owner', 'user_id' => $user?->id,
        ]);
    }

    public function test_missing_and_stale_account_reviews_cannot_restore_revoked_access(): void
    {
        $staff = $this->staff();
        $target = User::factory()->create(['role' => User::ROLE_GEODETIC]);
        $payload = $this->payload($target);
        $target->update(['is_active' => false, 'registration_status' => User::REGISTRATION_DECLINED]);
        $this->actingAs($staff)->put(route('staff.users.update', $target), $payload)
            ->assertSessionHasErrors('expected_account_revision');
        $this->assertFalse($target->fresh()->is_active);
        $this->assertSame(User::REGISTRATION_DECLINED, $target->fresh()->registration_status);
        unset($payload['expected_account_revision']);
        $this->put(route('staff.users.update', $target), $payload)
            ->assertSessionHasErrors('expected_account_revision');
        $this->assertDatabaseMissing('audit_logs', ['action' => 'user_updated', 'auditable_id' => $target->id]);
    }

    public function test_review_token_tracks_credentials_and_email_verification_but_ignores_activity(): void
    {
        $user = User::factory()->create();
        $review = app(UserAccountReviewService::class);
        $token = $review->revision($user);
        $user->update(['last_login_at' => now(), 'onboarding_state' => ['example' => 'done']]);
        $this->assertSame($token, $review->revision($user->fresh()));
        $user->forceFill(['password' => 'Different-Password-123!'])->save();
        $this->assertNotSame($token, $review->revision($user->fresh()));
        $token = $review->revision($user->fresh());
        $user->forceFill(['email_verified_at' => null])->save();
        $this->assertNotSame($token, $review->revision($user->fresh()));
    }

    public function test_a_landowner_link_changed_elsewhere_invalidates_an_account_review(): void
    {
        $staff = $this->staff();
        $target = User::factory()->create(['role' => User::ROLE_LANDOWNER]);
        $first = $this->landowner($target);
        $second = $this->landowner();
        $payload = $this->payload($target);
        DB::transaction(function () use ($first, $second, $target) {
            $first->update(['user_id' => null]);
            $second->update(['user_id' => $target->id]);
        });
        $this->actingAs($staff)->put(route('staff.users.update', $target), $payload)
            ->assertSessionHasErrors('expected_account_revision');
        $this->assertSame($target->id, $second->fresh()->user_id);
        $this->assertNull($first->fresh()->user_id);
    }

    public function test_current_review_can_relink_an_account_and_cannot_claim_an_occupied_record(): void
    {
        $staff = $this->staff();
        $target = User::factory()->create(['role' => User::ROLE_LANDOWNER]);
        $other = User::factory()->create(['role' => User::ROLE_LANDOWNER]);
        $first = $this->landowner($target);
        $second = $this->landowner();
        $occupied = $this->landowner($other);
        $this->actingAs($staff)->put(route('staff.users.update', $target),
            $this->payload($target, ['landowner_id' => $second->id]))
            ->assertSessionHasNoErrors()->assertRedirect(route('staff.users.index'));
        $this->assertNull($first->fresh()->user_id);
        $this->assertSame($target->id, $second->fresh()->user_id);
        $this->put(route('staff.users.update', $target),
            $this->payload($target, ['name' => 'Must Roll Back', 'landowner_id' => $occupied->id]))
            ->assertSessionHasErrors('landowner_id');
        $this->assertNotSame('Must Roll Back', $target->fresh()->name);
        $this->assertSame($other->id, $occupied->fresh()->user_id);
        $this->assertSame($target->id, $second->fresh()->user_id);
    }

    public function test_account_creation_cannot_displace_an_existing_link_or_leave_an_orphan_account(): void
    {
        $staff = $this->staff();
        $other = User::factory()->create(['role' => User::ROLE_LANDOWNER]);
        $occupied = $this->landowner($other);
        $count = User::count();
        $this->actingAs($staff)->post(route('staff.users.store'), [
            'name' => 'Blocked Account', 'username' => 'blocked_account',
            'role' => User::ROLE_LANDOWNER, 'is_active' => '1', 'landowner_id' => $occupied->id,
        ])->assertSessionHasErrors('landowner_id');
        $this->assertSame($count, User::count());
        $this->assertSame($other->id, $occupied->fresh()->user_id);
    }

    public function test_validation_preserves_unchecked_activation_and_stale_review_token(): void
    {
        $staff = $this->staff();
        $target = User::factory()->create(['role' => User::ROLE_GEODETIC, 'is_active' => true]);
        $payload = $this->payload($target, ['name' => '', 'is_active' => '0']);
        $this->actingAs($staff)->from(route('staff.users.edit', $target))
            ->put(route('staff.users.update', $target), $payload)->assertSessionHasErrors('name');
        $html = $this->get(route('staff.users.edit', $target))->assertOk()->getContent();
        $this->assertStringContainsString('value="'.$payload['expected_account_revision'].'"', $html);
        $this->assertMatchesRegularExpression('/id="is_active"[^>]*value="1"\s+class=/', $html);
        $this->assertStringContainsString('type="hidden" name="is_active" value="0"', $html);
    }

    public function test_user_list_renders_private_and_legacy_photos_and_missing_photo_fallback(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $staff = $this->staff();
        $private = User::factory()->create(['profile_photo_path' => 'profile-photos/private.png']);
        $legacy = User::factory()->create(['profile_photo_path' => 'profile-photos/legacy.png']);
        $missing = User::factory()->create(['profile_photo_path' => 'profile-photos/missing.png']);
        Storage::disk('local')->put($private->profile_photo_path, 'image');
        Storage::disk('public')->put($legacy->profile_photo_path, 'image');
        $this->actingAs($staff)->get(route('staff.users.index'))->assertOk()
            ->assertSee(route('profile.photo', $private), false)
            ->assertSee(route('profile.photo', $legacy), false)
            ->assertDontSee(route('profile.photo', $missing), false);
    }

    public function test_registration_validates_the_normalized_username_before_persisting_it(): void
    {
        Notification::fake();
        User::factory()->create(['username' => 'existing_owner']);
        $count = User::count();
        $this->post(route('register'), [
            'name' => 'Duplicate Owner', 'username' => 'EXISTING_OWNER',
            'password' => 'Strong-Password-123!', 'password_confirmation' => 'Strong-Password-123!',
            'privacy_consent' => '1',
        ])->assertSessionHasErrors('username');
        $this->assertSame($count, User::count());
        $this->post(route('register'), [
            'name' => 'New Owner', 'username' => 'NEW_OWNER',
            'password' => 'Strong-Password-123!', 'password_confirmation' => 'Strong-Password-123!',
            'privacy_consent' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect(route('landowner.registration.pending'));
        $this->assertDatabaseHas('users', ['username' => 'new_owner']);
    }
}
