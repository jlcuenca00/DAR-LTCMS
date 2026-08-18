<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\PasswordRecoveryCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailOtpPasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_without_email_is_directed_to_staff_assistance(): void
    {
        $user = User::factory()->create([
            'username' => 'no_email_user',
            'email' => null,
            'email_verified_at' => null,
        ]);

        $this->from(route('password.request'))
            ->post(route('password.recovery.identify'), [
                'username' => $user->username,
            ])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', function (string $status) {
                return str_contains($status, 'No email address is registered');
            });

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'action' => 'password_recovery_requested_without_email',
        ]);
    }

    public function test_registered_email_is_masked_and_must_match_before_code_is_sent(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'username' => 'email_user',
            'email' => 'carl.martin@gmail.com',
        ]);

        $this->post(route('password.recovery.identify'), [
            'username' => $user->username,
        ])->assertRedirect(route('password.request'));

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('c***.*****n@gmail.com')
            ->assertDontSee('carl.martin@gmail.com');

        $this->from(route('password.request'))
            ->post(route('password.recovery.confirm-email'), [
                'email' => 'wrong@gmail.com',
            ])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();

        $this->post(route('password.recovery.confirm-email'), [
            'email' => 'carl.martin@gmail.com',
        ])->assertRedirect(route('password.request'));

        Notification::assertSentTo($user, PasswordRecoveryCodeNotification::class);
    }

    public function test_valid_otp_uses_existing_forced_password_flow_without_temporary_password(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'username' => 'recover_me',
            'email' => 'recover@example.com',
            'password' => 'OldPassword123!',
            'must_change_password' => false,
        ]);

        $this->post(route('password.recovery.identify'), [
            'username' => $user->username,
        ])->assertRedirect(route('password.request'));

        $this->post(route('password.recovery.confirm-email'), [
            'email' => $user->email,
        ])->assertRedirect(route('password.request'));

        $code = null;
        Notification::assertSentTo(
            $user,
            PasswordRecoveryCodeNotification::class,
            function (PasswordRecoveryCodeNotification $notification) use (&$code) {
                $code = $notification->code;
                return true;
            }
        );

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $this->post(route('password.recovery.verify-code'), [
            'code' => $code,
        ])->assertRedirect(route('password.required'));

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->get(route('password.required'))
            ->assertOk()
            ->assertSee('registered email and verification code were confirmed');

        $newPassword = 'NewSecurePassword123!';

        $this->put(route('password.required.update'), [
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect(route('staff.dashboard'));

        $fresh = $user->fresh();
        $this->assertTrue(Hash::check($newPassword, $fresh->password));
        $this->assertFalse($fresh->must_change_password);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'action' => 'password_recovery_completed',
        ]);
    }

    public function test_incorrect_otp_does_not_authenticate_user(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'username' => 'wrong_code_user',
            'email' => 'wrong-code@example.com',
        ]);

        $this->post(route('password.recovery.identify'), [
            'username' => $user->username,
        ]);

        $this->post(route('password.recovery.confirm-email'), [
            'email' => $user->email,
        ]);

        $this->from(route('password.request'))
            ->post(route('password.recovery.verify-code'), [
                'code' => '000000',
            ])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_staff_can_create_account_without_email_and_database_stores_null(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->post(route('staff.users.store'), [
                'name' => 'No Email Staff',
                'username' => 'no_email_staff',
                'password' => 'InitialPassword123!',
                'password_confirmation' => 'InitialPassword123!',
                'role' => User::ROLE_STAFF,
                'is_active' => '1',
            ])
            ->assertRedirect(route('staff.users.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'no_email_staff',
            'email' => null,
        ]);
    }
}
