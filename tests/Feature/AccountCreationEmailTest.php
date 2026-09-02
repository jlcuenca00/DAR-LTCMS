<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountCreationEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_creation_emails_username_and_temporary_password_and_keeps_forced_change_enabled(): void
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
                'password' => 'Temporary-123!',
                'password_confirmation' => 'Temporary-123!',
                'role' => User::ROLE_GEODETIC,
                'is_active' => '1',
                'landowner_id' => null,
            ]);

        $response
            ->assertRedirect(route('staff.users.index'))
            ->assertSessionHas('success', function (string $message): bool {
                return str_contains($message, 'temporary password were emailed');
            });

        $created = User::query()->where('username', 'new_geo_user')->firstOrFail();

        $this->assertTrue(Hash::check('Temporary-123!', $created->password));
        $this->assertTrue($created->must_change_password);
        $this->assertNotNull($created->password_changed_at);

        Notification::assertSentTo(
            $created,
            AccountCreatedNotification::class,
            fn (AccountCreatedNotification $notification): bool => $notification->temporaryPassword === 'Temporary-123!'
        );

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staff->id,
            'action' => 'user_account_creation_email_sent',
            'auditable_type' => User::class,
            'auditable_id' => $created->id,
        ]);
    }

    public function test_account_creation_without_email_still_succeeds_without_sending_credentials(): void
    {
        Notification::fake();

        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->post(route('staff.users.store'), [
                'name' => 'No Email User',
                'username' => 'no_email_user',
                'email' => null,
                'password' => 'Temporary-123!',
                'password_confirmation' => 'Temporary-123!',
                'role' => User::ROLE_GEODETIC,
                'is_active' => '1',
                'landowner_id' => null,
            ])
            ->assertRedirect(route('staff.users.index'))
            ->assertSessionHas('success', function (string $message): bool {
                return str_contains($message, 'No confirmation email was sent');
            });

        Notification::assertNothingSent();

        $created = User::query()->where('username', 'no_email_user')->firstOrFail();
        $this->assertTrue($created->must_change_password);
    }

    public function test_account_creation_email_presents_credentials_and_first_login_password_change_notice(): void
    {
        $html = view('emails.account-created', [
            'name' => 'Sample User',
            'username' => 'sample_user',
            'temporaryPassword' => 'Temporary-123!',
            'isActive' => true,
            'loginUrl' => 'https://darltcms.me/login',
            'logoUrl' => 'https://darltcms.me/images/favicon.png',
        ])->render();

        $this->assertStringContainsString('sample_user', $html);
        $this->assertStringContainsString('Temporary-123!', $html);
        $this->assertStringContainsString('Password change required on first sign-in.', $html);
        $this->assertStringContainsString('DAR-LTCMS', $html);
    }
}
