<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordRecoveryCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailOtpPasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_identify_step_does_not_reveal_account_existence_or_recovery_eligibility(): void
    {
        $noEmail = User::factory()->create([
            'username' => 'no_email_user',
            'email' => null,
            'email_verified_at' => null,
            'is_active' => true,
        ]);

        $inactive = User::factory()->create([
            'username' => 'inactive_recovery_user',
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        foreach ([$noEmail->username, $inactive->username, 'unknown_recovery_user'] as $username) {
            $this->post(route('password.recovery.identify'), [
                'username' => $username,
            ])
                ->assertRedirect(route('password.request'))
                ->assertSessionHas('status', function (string $status) {
                    return str_contains($status, 'Continue by entering the registered recovery email');
                });

            $this->get(route('password.request'))
                ->assertOk()
                ->assertSee('Confirm Recovery Email')
                ->assertDontSee('No account was found')
                ->assertDontSee('No email address is registered')
                ->assertDontSee('currently inactive');
        }
    }

    public function test_registered_email_is_not_exposed_and_must_match_before_code_is_sent(): void
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
            ->assertSee('system intentionally does not display or mask the stored email address')
            ->assertDontSee('carl.martin@gmail.com')
            ->assertDontSee('c***.*****n@gmail.com');

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

        $sentCode = null;
        Notification::assertSentTo(
            $user,
            PasswordRecoveryCodeNotification::class,
            function (PasswordRecoveryCodeNotification $notification) use (&$sentCode) {
                $sentCode = $notification->code;
                return true;
            }
        );

        $wrongCode = $sentCode === '000000' ? '000001' : '000000';

        $this->from(route('password.request'))
            ->post(route('password.recovery.verify-code'), [
                'code' => $wrongCode,
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

    public function test_staff_temporary_password_reset_is_blocked_when_user_has_recovery_email(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_GEODETIC,
            'email' => 'geodetic@example.com',
        ]);
        $originalPassword = $user->password;

        $this->actingAs($staff)
            ->from(route('staff.users.edit', $user))
            ->post(route('staff.users.reset-password', $user))
            ->assertRedirect(route('staff.users.edit', $user))
            ->assertSessionHas('error', function (string $error) {
                return str_contains($error, 'registered email address');
            });

        $fresh = $user->fresh();
        $this->assertSame($originalPassword, $fresh->password);
        $this->assertFalse($fresh->must_change_password);
    }
}
