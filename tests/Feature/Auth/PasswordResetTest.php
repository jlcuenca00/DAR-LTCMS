<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_access_help_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Need help signing in?')
            ->assertSee('staff-managed usernames')
            ->assertDontSee('Email Password Reset Link');
    }

    public function test_email_password_reset_submission_is_disabled(): void
    {
        $this->post('/forgot-password', ['email' => 'user@example.com'])
            ->assertStatus(405);
    }

    public function test_token_reset_routes_are_not_available(): void
    {
        $this->get('/reset-password/example-token')->assertNotFound();
        $this->post('/reset-password')->assertNotFound();
    }
}
