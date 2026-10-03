<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use App\Notifications\EmailAddedVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountCreationEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user_page_collects_optional_email_without_manual_password_fields(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.users.create'))
            ->assertOk()
            ->assertSee('Email Address')
            ->assertSee('name="email"', false)
            ->assertDontSee('Initial Password')
            ->assertDontSee('name="password"', false)
            ->assertDontSee('name="password_confirmation"', false)
            ->assertSee('temporary password automatically');
    }

    public function test_edit_user_page_allows_staff_to_maintain_email_address(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_GEODETIC,
            'is_active' => true,
            'email' => 'existing@example.com',
        ]);

        $this->actingAs($staff)
            ->get(route('staff.users.edit', $user))
            ->assertOk()
            ->assertSee('Email Address')
            ->assertSee('name="email"', false)
            ->assertSee('existing@example.com');
    }

    public function test_account_creation_generates_and_emails_temporary_password_and_keeps_forced_change_enabled(): void
    {
        Notification::fake();

        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staff)
            ->post(route('staff.users.store'), [
                'name' => 'New Geodetic User',
                'username' => 'new_geo_user',
                'email' => 'new.geo@example.com',
                'role' => User::ROLE_GEODETIC,
                'is_active' => '1',
                'landowner_id' => null,
            ]);

        $response
            ->assertRedirect(route('staff.users.index'))
            ->assertSessionHas('success', function (string $message): bool {
                return str_contains($message, 'system-generated temporary password were emailed');
            });

        $created = User::query()->where('username', 'new_geo_user')->firstOrFail();
        $generatedPassword = null;

        Notification::assertSentTo(
            $created,
            AccountCreatedNotification::class,
            function (AccountCreatedNotification $notification) use (&$generatedPassword): bool {
                $generatedPassword = $notification->temporaryPassword;

                return strlen($generatedPassword) >= 12
                    && preg_match('/[a-z]/', $generatedPassword) === 1
                    && preg_match('/[A-Z]/', $generatedPassword) === 1
                    && preg_match('/[0-9]/', $generatedPassword) === 1
                    && preg_match('/[^A-Za-z0-9]/', $generatedPassword) === 1;
            }
        );

        $this->assertNotNull($generatedPassword);
        $this->assertTrue(Hash::check($generatedPassword, $created->password));
        $this->assertTrue($created->must_change_password);
        $this->assertNotNull($created->password_changed_at);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staff->id,
            'action' => 'user_account_creation_email_sent',
            'auditable_type' => User::class,
            'auditable_id' => $created->id,
        ]);
    }

    public function test_account_creation_without_email_shows_generated_temporary_password_once_to_staff(): void
    {
        Notification::fake();

        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staff)
            ->post(route('staff.users.store'), [
                'name' => 'No Email User',
                'username' => 'no_email_user',
                'email' => null,
                'role' => User::ROLE_GEODETIC,
                'is_active' => '1',
                'landowner_id' => null,
            ]);

        $created = User::query()->where('username', 'no_email_user')->firstOrFail();

        $response
            ->assertRedirect(route('staff.users.edit', $created))
            ->assertSessionHas('success', function (string $message): bool {
                return str_contains($message, 'system-generated temporary password is shown once');
            })
            ->assertSessionHas('temporary_password_username', 'no_email_user');

        $temporaryPassword = $response->getSession()->get('temporary_password');

        $this->assertIsString($temporaryPassword);
        $this->assertGreaterThanOrEqual(12, strlen($temporaryPassword));
        $this->assertMatchesRegularExpression('/[a-z]/', $temporaryPassword);
        $this->assertMatchesRegularExpression('/[A-Z]/', $temporaryPassword);
        $this->assertMatchesRegularExpression('/[0-9]/', $temporaryPassword);
        $this->assertMatchesRegularExpression('/[^A-Za-z0-9]/', $temporaryPassword);
        $this->assertTrue(Hash::check($temporaryPassword, $created->password));

        Notification::assertNothingSent();
        $this->assertTrue($created->must_change_password);
    }

    public function test_staff_adding_email_to_existing_account_sends_verification_email(): void
    {
        Notification::fake();

        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'name' => 'No Email User',
            'username' => 'existing_no_email',
            'email' => null,
            'email_verified_at' => null,
            'role' => User::ROLE_GEODETIC,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staff)
            ->put(route('staff.users.update', $target), [
                'expected_account_revision' => app(\App\Services\UserAccountReviewService::class)->revision($target->fresh()),
                'name' => $target->name,
                'username' => $target->username,
                'email' => 'new.email@example.com',
                'role' => $target->role,
                'is_active' => '1',
                'landowner_id' => null,
            ]);

        $response
            ->assertRedirect(route('staff.users.index'))
            ->assertSessionHas('success', function (string $message): bool {
                return str_contains($message, 'verification email was sent');
            });

        $target->refresh();

        $this->assertSame('new.email@example.com', $target->email);
        $this->assertNull($target->email_verified_at);

        Notification::assertSentTo(
            $target,
            EmailAddedVerificationNotification::class
        );

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staff->id,
            'action' => 'user_email_verification_email_sent',
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
        ]);
    }

    public function test_signed_added_email_verification_link_marks_only_current_address_verified(): void
    {
        $target = User::factory()->create([
            'username' => 'verify_added_email',
            'email' => 'verify.me@example.com',
            'email_verified_at' => null,
            'role' => User::ROLE_GEODETIC,
            'is_active' => true,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'email.added.verify',
            now()->addHours(24),
            [
                'user' => $target->id,
                'hash' => sha1(Str::lower($target->email)),
            ]
        );

        $this->get($verificationUrl)
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $target->refresh();
        $this->assertNotNull($target->email_verified_at);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $target->id,
            'action' => 'user_email_verified',
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
        ]);

        $oldVerificationUrl = URL::temporarySignedRoute(
            'email.added.verify',
            now()->addHours(24),
            [
                'user' => $target->id,
                'hash' => sha1(Str::lower($target->email)),
            ]
        );

        $target->forceFill([
            'email' => 'different.address@example.com',
            'email_verified_at' => null,
        ])->save();

        $this->get($oldVerificationUrl)->assertForbidden();
        $this->assertNull($target->fresh()->email_verified_at);
    }

    public function test_account_creation_email_presents_credentials_and_first_login_password_change_notice(): void
    {
        $html = view('emails.account-created', [
            'name' => 'Sample User',
            'username' => 'sample_user',
            'temporaryPassword' => 'Temporary-123!',
            'temporaryPasswordLifetimeHours' => 24,
            'isActive' => true,
            'loginUrl' => 'https://darltcms.me/login',
            'logoUrl' => 'https://darltcms.me/images/favicon.png',
        ])->render();

        $this->assertStringContainsString('sample_user', $html);
        $this->assertStringContainsString('Temporary-123!', $html);
        $this->assertStringContainsString('Password change required on first sign-in.', $html);
        $this->assertStringContainsString('expires 24 hours after it is generated', $html);
        $this->assertStringContainsString('DAR-LTCMS', $html);
    }
}
