<?php

namespace Tests\Feature;

use App\Models\Landowner;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\GoogleIdentity;
use App\Services\UserAccountReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function payload(User $user): array
    {
        return ['expected_account_revision' => app(UserAccountReviewService::class)->revision($user->fresh()), 'confirmation' => 'DELETE', 'reason' => 'Repeat Google registration test'];
    }

    private function staff(): User
    {
        return User::factory()->create(['role' => 'staff', 'is_active' => true]);
    }

    public function test_unused_account_is_permanently_deleted_and_reset_tokens_and_sessions_are_removed(): void
    {
        $staff = $this->staff();
        $user = User::factory()->create(['google_id' => 'test-google-id', 'role' => 'landowner']);
        DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => 'old-token', 'created_at' => now()]);
        DB::table('sessions')->insert(['id' => 'test-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($staff)->delete(route('staff.users.destroy', $user), $this->payload($user))->assertSessionHasNoErrors()->assertRedirect(route('staff.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user_deleted', 'actor_user_id' => $staff->id, 'auditable_id' => $user->id]);
    }

    public function test_google_account_with_login_history_can_register_again_without_deleting_audits(): void
    {
        Notification::fake();
        $staff = $this->staff();
        $user = User::factory()->create(['role' => 'landowner', 'google_id' => 'repeat-google', 'email' => 'repeat@gmail.com', 'registration_status' => User::REGISTRATION_PENDING]);
        $landowner = Landowner::create(['first_name' => 'Repeat', 'last_name' => 'Owner', 'user_id' => $user->id]);
        $event = AuditLogger::record('user_login', null, $user, [], $user->id);
        $this->actingAs($staff)->delete(route('staff.users.destroy', $user), $this->payload($user))->assertSessionHasNoErrors();
        $deleted = $user->fresh();
        $this->assertNotNull($deleted->account_deleted_at);
        $this->assertFalse($deleted->is_active);
        $this->assertNull($deleted->google_id);
        $this->assertNull($deleted->email);
        $this->assertNotSame($user->username, $deleted->username);
        $this->assertDatabaseHas('landowners', ['id' => $landowner->id, 'user_id' => null]);
        $this->assertDatabaseHas('audit_logs', ['id' => $event->id, 'actor_user_id' => $user->id]);
        $this->post(route('logout'));
        $this->mock(GoogleIdentity::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn(['sub' => 'repeat-google', 'email' => 'repeat@gmail.com', 'email_verified' => true, 'name' => 'Repeat Test']);
        });
        $this->post(route('register.google'), ['credential' => 'test-token', 'intent' => 'register', 'privacy_consent' => '1'])->assertSessionHasNoErrors()->assertRedirect(route('register.google.details'));
        $this->post(route('register.google.complete'), ['name' => 'Repeat Test', 'contact_number' => '09171234567', 'address' => 'Dumaguete City, Negros Oriental', 'privacy_consent' => '1'])->assertSessionHasNoErrors()->assertRedirect(route('landowner.registration.pending'));
        $new = User::where('google_id', 'repeat-google')->firstOrFail();
        $this->assertNotSame($user->id, $new->id);
        $this->assertAuthenticatedAs($new);
    }

    public function test_deleting_a_landowner_account_detaches_and_preserves_its_official_record(): void
    {
        $staff = $this->staff();
        $user = User::factory()->create(['role' => 'landowner']);
        $landowner = Landowner::create(['first_name' => 'Official', 'last_name' => 'Owner', 'user_id' => $user->id]);
        $this->actingAs($staff)->delete(route('staff.users.destroy', $user), $this->payload($user))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('landowners', ['id' => $landowner->id, 'first_name' => 'Official', 'last_name' => 'Owner', 'user_id' => null]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_self_deletion_missing_confirmation_and_stale_revision_are_rejected(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff)->delete(route('staff.users.destroy', $staff), $this->payload($staff))->assertSessionHasErrors('deletion');
        $user = User::factory()->create();
        $payload = $this->payload($user);
        $this->delete(route('staff.users.destroy', $user), array_merge($payload, ['confirmation' => 'yes']))->assertSessionHasErrors('confirmation');
        $user->update(['name' => 'Changed after review']);
        $this->delete(route('staff.users.destroy', $user), $payload)->assertSessionHasErrors('expected_account_revision');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_non_staff_cannot_delete_or_revoke_sessions(): void
    {
        $target = User::factory()->create();
        foreach (['geodetic', 'landowner'] as $role) {
            $actor = User::factory()->create(['role' => $role]);
            $this->actingAs($actor)->delete(route('staff.users.destroy', $target), $this->payload($target))->assertForbidden();
            $this->post(route('staff.users.revoke-sessions', $target), $this->payload($target))->assertForbidden();
        }
    }

    public function test_session_revocation_changes_version_and_preserves_password(): void
    {
        $staff = $this->staff();
        $target = User::factory()->create(['password_changed_at' => null]);
        $hash = $target->password;
        $this->actingAs($staff)->post(route('staff.users.revoke-sessions', $target), $this->payload($target))->assertSessionHasNoErrors();
        $target->refresh();
        $this->assertSame($hash, $target->password);
        $this->assertNull($target->password_changed_at);
        $this->assertSame(1, $target->session_version);
        $this->actingAs($target)->withSession(['auth_password_changed_at' => null])->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post(route('login'), ['username' => $target->username, 'password' => 'password'])->assertSessionHasNoErrors();
        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_google_sign_in_works_after_sessions_are_revoked(): void
    {
        Notification::fake();
        $staff = $this->staff();
        $target = User::factory()->create(['role' => 'landowner', 'google_id' => 'revoked-google']);
        $this->actingAs($staff)->post(route('staff.users.revoke-sessions', $target), $this->payload($target))->assertSessionHasNoErrors();
        $this->post(route('logout'));
        $this->mock(GoogleIdentity::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn(['sub' => 'revoked-google', 'email' => 'example@gmail.com', 'email_verified' => true]);
        });
        $this->post(route('register.google'), ['credential' => 'test-token', 'intent' => 'login'])->assertSessionHasNoErrors();
        $this->assertSame(1, session('auth_session_version'));
        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_deleted_identity_cannot_be_reactivated_and_is_hidden_from_active_and_inactive_lists(): void
    {
        $staff = $this->staff();
        $target = User::factory()->create(['name' => 'Deleted Test Identity', 'username' => 'deleted_test_identity']);
        AuditLogger::record('user_login', null, $target, [], $target->id);
        $this->actingAs($staff)->delete(route('staff.users.destroy', $target), $this->payload($target))->assertSessionHasNoErrors();
        $this->get(route('staff.users.index'))->assertDontSee('Deleted Test Identity');
        $this->get(route('staff.users.index', ['status' => 'inactive']))->assertDontSee('Deleted Test Identity');
        $this->get(route('staff.users.index', ['status' => 'deleted']))->assertSee('Deleted Test Identity');
        $this->put(route('staff.users.update', $target), array_merge($this->payload($target), ['name' => 'Restored', 'username' => $target->username, 'email' => null, 'role' => $target->role, 'is_active' => '1']))->assertSessionHasErrors('account');
    }

    public function test_account_and_profile_pages_render_for_each_role(): void
    {
        $staff = $this->staff();
        foreach (['staff', 'landowner', 'geodetic'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($staff)->get(route('staff.users.edit', $user))->assertOk()->assertSee('Access & linked records', false)->assertSee('Delete account');
            $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('My access')->assertSee($user->name);
        }
    }
}
