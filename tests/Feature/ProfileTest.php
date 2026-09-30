<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailAddedVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertOk();
    }

    public function test_profile_information_can_be_updated_without_password_when_email_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('Test User', $user->fresh()->name);
    }

    public function test_profile_email_change_requires_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => 'new@example.com',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame('old@example.com', $user->fresh()->email);
    }

    public function test_profile_email_change_password_challenge_is_rate_limited(): void
    {
        $user = User::factory()->create([
            'email' => 'rate-old@example.com',
        ]);

        $this->actingAs($user);

        foreach (range(1, 5) as $attempt) {
            $this->patch('/profile', [
                'name' => $user->name,
                'email' => 'rate-new@example.com',
                'current_password' => 'wrong-password',
            ])->assertSessionHasErrors('current_password');
        }

        $this->patch('/profile', [
            'name' => $user->name,
            'email' => 'rate-new@example.com',
            'current_password' => 'wrong-password',
        ])->assertSessionHasErrors([
            'current_password' => fn (string $message) => str_contains($message, 'Too many incorrect password attempts'),
        ]);

        $this->assertSame('rate-old@example.com', $user->fresh()->email);
    }

    public function test_profile_email_change_resets_verification_and_sends_signed_verification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => 'new@example.com',
                'current_password' => 'password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile')
            ->assertSessionHas('email_verification_status');

        $user->refresh();

        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, EmailAddedVerificationNotification::class);
    }

    public function test_recent_password_confirmation_allows_email_change_without_reentering_password(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'confirmed-old@example.com',
        ]);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->patch('/profile', [
                'name' => $user->name,
                'email' => 'confirmed-new@example.com',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('confirmed-new@example.com', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_email_is_optional_for_username_based_accounts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'Username Account',
                'email' => null,
                'current_password' => 'password',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($user->refresh()->email);
    }

    public function test_users_cannot_delete_their_own_government_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertStatus(405);

        $this->assertNotNull($user->fresh());
    }
}
