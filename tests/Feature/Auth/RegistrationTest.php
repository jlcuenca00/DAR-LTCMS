<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\LandownerRegistrationReceived;
use App\Services\GoogleIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function googlePayload(array $overrides = []): void
    {
        $this->mock(GoogleIdentity::class, function ($mock) use ($overrides) {
            $mock->shouldReceive('verify')->once()->andReturn(array_merge([
                'sub' => 'google-test-id',
                'email' => 'landowner@example.com',
                'email_verified' => true,
                'name' => 'Test Landowner',
            ], $overrides));
        });
    }

    private function registerGoogle()
    {
        return $this->post(route('register.google'), [
            'credential' => 'test-token', 'intent' => 'register', 'privacy_consent' => '1',
        ]);
    }

    public function test_google_registration_sends_a_pending_review_receipt(): void
    {
        Notification::fake();
        $this->googlePayload();
        $this->registerGoogle()->assertRedirect(route('landowner.registration.pending'));
        $user = User::where('google_id', 'google-test-id')->firstOrFail();
        $this->assertSame(User::REGISTRATION_PENDING, $user->registration_status);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, LandownerRegistrationReceived::class);
        $mail = (new LandownerRegistrationReceived)->toMail($user);
        $this->assertSame(route('login'), $mail->actionUrl);
        $this->assertStringContainsString('not an approval', implode(' ', $mail->introLines));
    }

    public function test_existing_google_registration_shows_conflict_without_signing_in(): void
    {
        Notification::fake();
        User::factory()->create(['google_id' => 'google-test-id']);
        $this->googlePayload();
        $count = User::count();
        $this->registerGoogle()->assertStatus(409)->assertViewIs('auth.account-exists')
            ->assertSee('This account already exists.')
            ->assertSee('Proceed to sign in')->assertSee(route('login'), false);
        $this->assertGuest();
        $this->assertSame($count, User::count());
        Notification::assertNothingSent();
    }

    public function test_existing_email_is_not_automatically_linked_to_google(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'Landowner@Example.com']);
        $this->googlePayload();
        $this->registerGoogle()->assertStatus(409);
        $this->assertNull($user->fresh()->google_id);
        $this->assertGuest();
        Notification::assertNothingSent();
    }

    public function test_existing_google_account_can_still_sign_in(): void
    {
        Notification::fake();
        $user = User::factory()->create(['google_id' => 'google-test-id', 'is_active' => true]);
        $this->googlePayload();
        $this->post(route('register.google'), [
            'credential' => 'test-token', 'intent' => 'login',
        ])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        Notification::assertNothingSent();
    }

    public function test_unverified_google_email_cannot_register_or_receive_mail(): void
    {
        Notification::fake();
        $this->googlePayload(['email_verified' => false]);
        $this->registerGoogle()->assertSessionHasErrors('google');
        $this->assertGuest();
        Notification::assertNothingSent();
    }

    public function test_email_failure_does_not_undo_registration(): void
    {
        $this->googlePayload();
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        $this->registerGoogle()->assertRedirect(route('landowner.registration.pending'))
            ->assertSessionHas('registration_email_warning');
        $this->assertSame(User::REGISTRATION_PENDING, User::where('google_id', 'google-test-id')->firstOrFail()->registration_status);
        $this->assertAuthenticated();
    }

    public function test_google_buttons_are_english_and_pill_shaped(): void
    {
        config(['services.google.client_id' => 'test-client']);
        foreach (['/login', '/register'] as $url) {
            $this->get($url)->assertOk()->assertSee('data-locale="en"', false)
                ->assertSee('data-shape="pill"', false)->assertSee('gsi/client?hl=en', false);
        }
    }

    public function test_landowner_can_register_without_email_and_is_kept_pending(): void
    {
        $response = $this->post('/register', [
            'name' => 'Juan Dela Cruz',
            'username' => 'juan_landowner',
            'email' => '',
            'password' => 'Example-Password-123!',
            'password_confirmation' => 'Example-Password-123!',
            'privacy_consent' => '1',
        ]);

        $user = User::query()->where('username', 'juan_landowner')->first();

        $response->assertRedirect(route('landowner.registration.pending'));
        $this->assertNotNull($user);
        $this->assertSame(User::ROLE_LANDOWNER, $user->role);
        $this->assertSame(User::REGISTRATION_PENDING, $user->registration_status);
        $this->assertNull($user->email);
    }

    public function test_pending_landowner_cannot_open_landowner_records(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'registration_status' => User::REGISTRATION_PENDING,
        ]);

        $this->actingAs($user)
            ->get('/landowner/dashboard')
            ->assertRedirect(route('landowner.registration.pending'));
    }
}
