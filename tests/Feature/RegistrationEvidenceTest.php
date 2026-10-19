<?php

namespace Tests\Feature;

use App\Models\Landowner;
use App\Models\User;
use App\Services\RegistrationEvidence;
use App\Services\UserAccountReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsRegistrationEvidence;
use Tests\TestCase;

class RegistrationEvidenceTest extends TestCase
{
    use RefreshDatabase, BuildsRegistrationEvidence;

    private function registration(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria Santos', 'username' => 'evidence_owner', 'email' => 'evidence@example.com',
            'password' => 'Strong-Password-123!', 'password_confirmation' => 'Strong-Password-123!',
            'contact_number' => '09171234567', 'address' => 'Dumaguete City', 'privacy_consent' => '1',
        ], $this->evidenceFiles(), $overrides);
    }

    public function test_registration_stores_private_images_and_staff_can_review_them(): void
    {
        Notification::fake();
        $this->post(route('register'), $this->registration())->assertSessionHasNoErrors();
        $owner = User::where('username', 'evidence_owner')->firstOrFail();
        $this->assertSame('passport', $owner->identity_evidence['id_type']);
        foreach (['id_front', 'selfie'] as $kind) Storage::disk('local')->assertExists($owner->identity_evidence[$kind]);
        $this->assertArrayNotHasKey('identity_evidence', $owner->toArray());
        $this->assertDatabaseCount('landowners', 0);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $this->actingAs($staff)->get(route('staff.users.edit', $owner))->assertOk()->assertSee('Submitted identity evidence')->assertSee('Submitted selfie');
        $response = $this->get(route('staff.users.identity-evidence', [$owner, 'id_front']))->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'identity_evidence_viewed', 'actor_user_id' => $staff->id]);
        $this->get(route('staff.users.identity-evidence', [$owner, 'id_back']))->assertNotFound();
        $this->get(route('staff.users.identity-evidence', [$owner, 'unknown']))->assertNotFound();
    }

    public function test_non_staff_cannot_view_identity_images_even_for_their_own_account(): void
    {
        Notification::fake();
        $this->post(route('register'), $this->registration());
        $owner = User::where('username', 'evidence_owner')->firstOrFail();
        // Pending-registration middleware intentionally redirects the pending owner.
        $this->get(route('staff.users.identity-evidence', [$owner, 'selfie']))->assertRedirect(route('landowner.registration.pending'));
        $other = User::factory()->create(['role' => User::ROLE_GEODETIC]);
        $this->actingAs($other)->get(route('staff.users.identity-evidence', [$owner, 'selfie']))->assertForbidden();
        $this->post(route('logout'));
        $this->get(route('staff.users.identity-evidence', [$owner, 'selfie']))->assertRedirect(route('login'));
    }

    public function test_registration_requires_supported_images_and_explicit_evidence_consent(): void
    {
        $payload = $this->registration([
            'id_type' => 'unsupported', 'identity_consent' => '0',
            'id_front' => UploadedFile::fake()->createWithContent('bad.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
            'selfie' => UploadedFile::fake()->image('small.jpg', 40, 40),
        ]);
        $this->post(route('register'), $payload)->assertSessionHasErrors(['id_type', 'id_front', 'selfie', 'identity_consent']);
        $this->assertDatabaseCount('users', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_registration_rejects_images_over_two_megabytes(): void
    {
        $payload = $this->registration(['id_front' => UploadedFile::fake()->image('large.jpg', 640, 400)->size(2049)]);
        $this->post(route('register'), $payload)->assertSessionHasErrors('id_front');
        $this->assertDatabaseCount('users', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_deleting_an_account_removes_its_evidence_but_preserves_the_landowner_record(): void
    {
        Notification::fake();
        $this->post(route('register'), $this->registration());
        $owner = User::where('username', 'evidence_owner')->firstOrFail();
        $evidence = $owner->identity_evidence;
        $record = Landowner::create(['first_name' => 'Maria', 'last_name' => 'Santos', 'user_id' => $owner->id]);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $this->actingAs($staff)->delete(route('staff.users.destroy', $owner), [
            'expected_account_revision' => app(UserAccountReviewService::class)->revision($owner->fresh()),
            'confirmation' => 'DELETE', 'reason' => 'Repeat registration test',
        ])->assertSessionHasNoErrors();
        foreach (['id_front', 'selfie'] as $kind) Storage::disk('local')->assertMissing($evidence[$kind]);
        $this->assertDatabaseHas('landowners', ['id' => $record->id, 'user_id' => null]);
        $this->assertNull($owner->fresh()->identity_evidence);
        $this->get(route('staff.users.identity-evidence', [$owner, 'selfie']))->assertNotFound();
    }

    public function test_camera_permission_is_limited_to_registration_pages(): void
    {
        $this->get(route('register'))->assertOk()->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
        $this->get(route('login'))->assertOk()->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
