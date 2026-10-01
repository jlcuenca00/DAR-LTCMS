<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_users_can_authenticate_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'staff_tester',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/staff/dashboard');
    }

    public function test_user_with_temporary_password_is_sent_to_required_change_page(): void
    {
        $user = User::factory()->create([
            'username' => 'temporary_user',
            'password' => Hash::make('temporary123'),
            'must_change_password' => true,
            'password_changed_at' => now(),
            'temporary_password_expires_at' => now()->addHour(),
        ]);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'temporary123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('password.required'));
    }

    public function test_unbounded_or_expired_temporary_password_cannot_authenticate(): void
    {
        foreach ([null, now()->subMinute()] as $expiresAt) {
            $user = User::factory()->create([
                'username' => 'expired_temp_'.($expiresAt ? 'past' : 'null'),
                'password' => Hash::make('Temporary-123!'),
                'must_change_password' => true,
                'password_changed_at' => now(),
                'temporary_password_expires_at' => $expiresAt,
            ]);

            $this->post('/login', [
                'username' => $user->username,
                'password' => 'Temporary-123!',
            ])->assertSessionHasErrors('username');

            $this->assertGuest();
        }
    }

    public function test_login_has_an_ip_wide_password_spraying_limit(): void
    {
        foreach (range(1, 30) as $attempt) {
            $this->post('/login', [
                'username' => 'spray-user-'.$attempt,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('username');
        }

        $response = $this->post('/login', [
            'username' => 'spray-user-31',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('username');

        $errors = $response->getSession()->get('errors');
        $this->assertStringContainsString('Too many login attempts', $errors->first('username'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create(['username' => 'valid_user']);

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_password_confirmation_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $this->actingAs($user);

        foreach (range(1, 6) as $attempt) {
            $this->post('/confirm-password', [
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('password');
        }

        $this->post('/confirm-password', [
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
