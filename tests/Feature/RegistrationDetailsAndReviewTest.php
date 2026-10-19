<?php

namespace Tests\Feature;

use App\Models\Landowner;
use App\Models\User;
use App\Notifications\LandownerRegistrationReviewed;
use App\Services\UserAccountReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationDetailsAndReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_registration_rejects_future_birth_dates_and_missing_spouse_details(): void
    {
        $this->post(route('register'), [
            'name' => 'Maria Santos', 'username' => 'maria', 'email' => 'maria@example.com',
            'contact_number' => '09171234567', 'address' => 'Dumaguete City',
            'password' => 'Strong-Password-123!', 'password_confirmation' => 'Strong-Password-123!',
            'date_of_birth' => now()->addDay()->toDateString(), 'registered_owner_status' => 'married',
            'privacy_consent' => '1',
        ])->assertSessionHasErrors(['date_of_birth', 'spouse_name']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_review_email_is_sent_when_status_changes_but_not_for_ordinary_edits(): void
    {
        Notification::fake();
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $user = User::factory()->create(['role' => User::ROLE_LANDOWNER, 'registration_status' => User::REGISTRATION_PENDING]);
        $record = Landowner::create(['first_name' => 'Maria', 'last_name' => 'Santos']);
        $payload = [
            'expected_account_revision' => app(UserAccountReviewService::class)->revision($user),
            'name' => $user->name, 'username' => $user->username, 'email' => $user->email,
            'role' => User::ROLE_LANDOWNER, 'is_active' => '1', 'landowner_id' => $record->id,
            'registration_status' => User::REGISTRATION_APPROVED,
        ];
        $this->actingAs($staff)->put(route('staff.users.update', $user), $payload)->assertSessionHasNoErrors();
        Notification::assertSentTo($user, LandownerRegistrationReviewed::class);
        $this->assertSame(User::REGISTRATION_APPROVED, $user->fresh()->registration_status);
        Notification::fake();
        $payload['expected_account_revision'] = app(UserAccountReviewService::class)->revision($user->fresh());
        $this->put(route('staff.users.update', $user), $payload)->assertSessionHasNoErrors();
        Notification::assertNothingSent();
    }

    public function test_review_email_failure_does_not_undo_the_saved_review(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $user = User::factory()->create(['role' => User::ROLE_LANDOWNER, 'registration_status' => User::REGISTRATION_PENDING]);
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        $this->actingAs($staff)->put(route('staff.users.update', $user), [
            'expected_account_revision' => app(UserAccountReviewService::class)->revision($user),
            'name' => $user->name, 'username' => $user->username, 'email' => $user->email,
            'role' => User::ROLE_LANDOWNER, 'is_active' => '1',
            'registration_status' => User::REGISTRATION_DECLINED,
        ])->assertSessionHasNoErrors()->assertSessionHas('success', fn ($message) => str_contains($message, 'review email could not be sent'));
        $this->assertSame(User::REGISTRATION_DECLINED, $user->fresh()->registration_status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'registration_review_email_failed']);
    }

    public function test_pending_page_has_email_and_business_day_guidance_without_an_office_visit(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_LANDOWNER, 'registration_status' => User::REGISTRATION_PENDING]);
        $this->actingAs($user)->get(route('landowner.registration.pending'))
            ->assertOk()->assertSee('few business days')->assertSee('receive an email')
            ->assertDontSee('visit/contact')->assertDontSee('Next step: contact');
    }
}
