<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailAddedVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserAddedEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_adding_email_to_existing_account_sends_verification_email(): void
    {
        Notification::fake();

        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'name' => 'No Email User',
            'username' => 'no_email_user',
            'email' => null,
            'email_verified_at' => null,
            'role' => User::ROLE_GEODETIC,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staff)
            ->put(route('staff.users.update', $target), [
                'name' => $target->name,
                'username' => $target->username,
                'email' => 'new.email@example.com',
                'role' => $target->role,
                'is_active' => '1',
                'landowner_id' => null,
            ]);

        $response
            ->assertRedirect(route('staff.users.index'))
            ->assertSessionHas('success');

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

    public function test_signed_email_link_marks_the_current_added_email_as_verified(): void
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
    }

    public function test_old_verification_link_cannot_verify_a_different_current_email(): void
    {
        $target = User::factory()->create([
            'username' => 'changed_email_user',
            'email' => 'first.address@example.com',
            'email_verified_at' => null,
            'role' => User::ROLE_GEODETIC,
            'is_active' => true,
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
            'email' => 'second.address@example.com',
            'email_verified_at' => null,
        ])->save();

        $this->get($oldVerificationUrl)->assertForbidden();

        $target->refresh();
        $this->assertNull($target->email_verified_at);
    }
}
