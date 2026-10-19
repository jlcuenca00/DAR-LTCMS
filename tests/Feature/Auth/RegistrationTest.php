<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\EmailAddedVerificationNotification;
use App\Notifications\LandownerRegistrationReceived;
use App\Services\GoogleIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\BuildsRegistrationEvidence;

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

    private function beginGoogle()
    {
        return $this->post(route('register.google'), [
            'credential' => 'test-token', 'intent' => 'register', 'privacy_consent' => '1',
        ]);
    }

    private function completeGoogle(array $overrides = [])
    {
        return $this->post(route('register.google.complete'), array_merge([
            'name' => 'Test Landowner',
            'contact_number' => '0917 123 4567',
            'address' => 'Barangay Test, Dumaguete City, Negros Oriental',
            'privacy_consent' => '1',
        ], $this->evidenceFiles(), $overrides));
    }

    private function registerGoogle()
    {
        $this->beginGoogle()->assertRedirect(route('register.google.details'));

        return $this->completeGoogle();
    }

    public function test_google_identity_does_not_create_an_account_before_details_are_submitted(): void
    {
        Notification::fake();
        $this->googlePayload();
        $this->beginGoogle()->assertRedirect(route('register.google.details'));
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        Notification::assertNothingSent();
        $this->get(route('register.google.details'))->assertOk()->assertSee('Test Landowner')->assertSee('landowner@example.com');
        $this->completeGoogle(['date_of_birth' => '1990-01-01', 'registered_owner_status' => 'single', 'name' => 'Juan Dela Cruz', 'email' => 'attacker@gmail.com', 'google_id' => 'attacker', 'role' => 'staff'])->assertRedirect(route('landowner.registration.pending'))->assertSessionMissing('google_registration');
        $user = User::where('google_id', 'google-test-id')->firstOrFail();
        $this->assertSame('Juan Dela Cruz', $user->name);
        $this->assertSame('landowner@example.com', $user->email);
        $this->assertSame(User::ROLE_LANDOWNER, $user->role);
        $this->assertSame('0917 123 4567', $user->registration_details['contact_number']);
        $this->assertSame('1990-01-01', $user->registration_details['date_of_birth']);
        $this->assertSame('single', $user->registration_details['registered_owner_status']);
        $this->assertDatabaseCount('landowners', 0);
    }

    public function test_missing_or_expired_google_session_cannot_create_an_account(): void
    {
        $this->get(route('register.google.details'))->assertRedirect(route('register'));
        $this->completeGoogle()->assertRedirect(route('register'));
        $this->googlePayload();
        $this->beginGoogle();
        $this->travel(16)->minutes();
        $this->completeGoogle()->assertRedirect(route('register'))->assertSessionHasErrors('google')->assertSessionMissing('google_registration');
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_google_details_require_contact_address_and_consent_and_can_be_cancelled(): void
    {
        $this->googlePayload();
        $this->beginGoogle();
        $this->from(route('register.google.details'))->completeGoogle(['name' => ' ', 'contact_number' => 'abc', 'address' => '', 'privacy_consent' => '0'])
            ->assertRedirect(route('register.google.details'))->assertSessionHasErrors(['name', 'contact_number', 'address', 'privacy_consent']);
        $this->assertDatabaseCount('users', 0);
        $this->post(route('register.google.cancel'))->assertRedirect(route('register'))->assertSessionMissing('google_registration');
        $this->completeGoogle()->assertRedirect(route('register'));
    }

    public function test_submitted_google_details_are_visible_to_staff_for_review(): void
    {
        Notification::fake();
        $this->googlePayload();
        $this->registerGoogle();
        $user = User::where('google_id', 'google-test-id')->firstOrFail();
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $this->actingAs($staff)->get(route('staff.users.edit', $user))
            ->assertOk()->assertSee('Submitted registration details')
            ->assertSee('0917 123 4567')->assertSee('Barangay Test, Dumaguete City, Negros Oriental');
    }

    public function test_google_completion_rechecks_duplicate_identity(): void
    {
        Notification::fake();
        $this->googlePayload();
        $this->beginGoogle();
        User::factory()->create(['email' => 'landowner@example.com']);
        $this->completeGoogle()->assertStatus(409)->assertSessionMissing('google_registration');
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
        Notification::assertNothingSent();
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
        Notification::assertSentTo($user, EmailAddedVerificationNotification::class);
        $this->assertNull($user->email_verified_at);
        $mail = (new LandownerRegistrationReceived)->toMail($user);
        $this->assertSame('emails.landowner-registration-received', $mail->view);
        $html = view($mail->view, $mail->viewData)->render();
        $this->assertStringContainsString(route('login'), $html);
        $this->assertStringContainsString('not an approval', $html);
        $this->assertStringContainsString('Pending DAR review', $html);
        $this->assertStringContainsString(asset('images/favicon.png'), $html);
    }

    public function test_gmail_google_registration_is_locally_verified_without_extra_email_challenge(): void
    {
        Notification::fake();
        $this->googlePayload([
            'email' => 'landowner@gmail.com',
        ]);

        $this->registerGoogle()->assertRedirect(route('landowner.registration.pending'));

        $user = User::where('google_id', 'google-test-id')->firstOrFail();

        $this->assertNotNull($user->email_verified_at);
        Notification::assertSentTo($user, LandownerRegistrationReceived::class);
        Notification::assertNotSentTo($user, EmailAddedVerificationNotification::class);
    }

    public function test_workspace_google_registration_is_locally_verified_when_hd_claim_is_present(): void
    {
        Notification::fake();
        $this->googlePayload([
            'email' => 'landowner@agency.example',
            'hd' => 'agency.example',
        ]);

        $this->registerGoogle()->assertRedirect(route('landowner.registration.pending'));

        $user = User::where('google_id', 'google-test-id')->firstOrFail();

        $this->assertNotNull($user->email_verified_at);
        Notification::assertNotSentTo($user, EmailAddedVerificationNotification::class);
    }

    public function test_workspace_hd_must_match_email_domain_before_local_verification_is_trusted(): void
    {
        Notification::fake();
        $this->googlePayload([
            'email' => 'landowner@other.example',
            'hd' => 'agency.example',
        ]);

        $this->registerGoogle()->assertRedirect(route('landowner.registration.pending'));

        $user = User::where('google_id', 'google-test-id')->firstOrFail();

        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, EmailAddedVerificationNotification::class);
    }

    public function test_existing_google_registration_shows_conflict_without_signing_in(): void
    {
        Notification::fake();
        User::factory()->create(['google_id' => 'google-test-id']);
        $this->googlePayload();
        $count = User::count();
        $this->beginGoogle()->assertStatus(409)->assertViewIs('auth.account-exists')
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
        $this->beginGoogle()->assertStatus(409);
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
        $this->beginGoogle()->assertSessionHasErrors('google');
        $this->assertGuest();
        Notification::assertNothingSent();
    }

    public function test_email_failure_does_not_undo_registration(): void
    {
        $this->googlePayload(['email' => 'landowner@gmail.com']);
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

    public function test_manual_registration_email_stays_unverified_until_signed_link_is_used(): void
    {
        Notification::fake();

        $this->post('/register', array_merge([
            'name' => 'Maria Santos',
            'username' => 'maria_landowner',
            'contact_number' => '09171234567',
            'address' => 'Dumaguete City, Negros Oriental',
            'date_of_birth' => '1990-01-01',
            'registered_owner_status' => 'married',
            'spouse_name' => 'Juan Santos',
            'email' => 'maria@example.com',
            'password' => 'Example-Password-123!',
            'password_confirmation' => 'Example-Password-123!',
            'privacy_consent' => '1',
        ], $this->evidenceFiles()))->assertRedirect(route('landowner.registration.pending'));

        $user = User::query()->where('username', 'maria_landowner')->firstOrFail();

        $this->assertNull($user->email_verified_at);
        $this->assertSame('Juan Santos', $user->registration_details['spouse_name']);
        $this->assertSame('1990-01-01', $user->registration_details['date_of_birth']);
        Notification::assertSentTo($user, EmailAddedVerificationNotification::class);
        Notification::assertSentTo($user, LandownerRegistrationReceived::class);
    }

    public function test_new_manual_registration_requires_email_and_contact_details(): void
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

        $response->assertSessionHasErrors(['email', 'contact_number', 'address']);
        $this->assertNull($user);
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
