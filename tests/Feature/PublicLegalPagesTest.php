<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_pages_are_public_and_contain_the_published_information(): void
    {
        $this->get('/privacy-policy')->assertOk()
            ->assertSee('Privacy Policy')->assertSee('Google sign-in')
            ->assertSee('dar_legal_orneg@yahoo.com');
        $this->get('/terms-of-service')->assertOk()
            ->assertSee('Terms of Service')->assertSee('Registration and review');
    }

    public function test_public_entry_pages_link_to_both_policies(): void
    {
        foreach (['/', '/login', '/register'] as $path) {
            $this->get($path)->assertOk()
                ->assertSee(route('privacy'), false)
                ->assertSee(route('terms'), false);
        }
    }

    public function test_registration_uses_the_dar_authentication_theme(): void
    {
        config(['services.google.client_id' => 'test-client']);
        $this->get('/register')->assertOk()
            ->assertSee('images/login-bg.png', false)
            ->assertSee('images/dar-logo.svg', false)
            ->assertSee('login-card', false)
            ->assertSee('form-input', false)
            ->assertSee('data-ux_mode="popup"', false)
            ->assertSee('name="privacy_consent"', false)
            ->assertSee('Email (optional)');
    }

    public function test_pending_and_declined_accounts_can_read_policies_without_unlocking_records(): void
    {
        foreach ([User::REGISTRATION_PENDING, User::REGISTRATION_DECLINED] as $status) {
            $user = User::factory()->create([
                'role' => User::ROLE_LANDOWNER,
                'registration_status' => $status,
                'is_active' => true,
                'must_change_password' => true,
            ]);
            $this->actingAs($user)->get('/privacy-policy')->assertOk();
            $this->get('/terms-of-service')->assertOk();
            $this->get('/landowner/dashboard')
                ->assertRedirect(route('landowner.registration.pending'));
        }
    }

    public function test_registration_has_one_shared_consent_with_and_without_google(): void
    {
        foreach (['test-client', null] as $clientId) {
            config(['services.google.client_id' => $clientId]);
            $response = $this->get('/register')->assertOk();
            $html = $response->getContent();

            $this->assertSame(1, substr_count($html, 'I have read the'));
            $this->assertSame(1, substr_count($html, 'type="checkbox"'));
            $response->assertSee('id="registration-privacy-consent" form="manual-registration-form"', false);
            $response->assertSee('id="manual-registration-form"', false);
        }
    }

    public function test_login_uses_forgot_password_label(): void
    {
        $this->get('/login')->assertOk()->assertSee('Forgot password?')
            ->assertDontSee('Need Help Signing In?');
    }
}
