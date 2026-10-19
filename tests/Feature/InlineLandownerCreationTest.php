<?php

namespace Tests\Feature;

use App\Models\Landowner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InlineLandownerCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_a_record_as_json_without_linking_or_approving_the_account(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $applicant = User::factory()->create(['role' => User::ROLE_LANDOWNER, 'registration_status' => User::REGISTRATION_PENDING]);
        $response = $this->actingAs($staff)->postJson(route('staff.records.landowners.store'), [
            'first_name' => 'Maria', 'last_name' => 'Santos', 'contact_number' => '09171234567',
        ])->assertCreated()->assertJsonStructure(['id', 'text']);
        $record = Landowner::findOrFail($response->json('id'));
        $this->assertNull($record->user_id);
        $this->assertSame(User::REGISTRATION_PENDING, $applicant->fresh()->registration_status);
        $this->assertNull($applicant->fresh()->landowner);
        $this->assertDatabaseHas('audit_logs', ['action' => 'landowner_record_created', 'auditable_id' => $record->id, 'actor_user_id' => $staff->id]);
    }

    public function test_inline_creation_uses_existing_landowner_validation(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $this->actingAs($staff)->postJson(route('staff.records.landowners.store'), [
            'first_name' => '', 'last_name' => 'Santos', 'registered_owner_status' => 'married',
        ])->assertUnprocessable()->assertJsonValidationErrors(['first_name', 'spouse_name']);
        $this->assertDatabaseCount('landowners', 0);
    }

    public function test_non_staff_cannot_create_landowner_records_as_json(): void
    {
        foreach ([User::ROLE_LANDOWNER, User::ROLE_GEODETIC] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->postJson(route('staff.records.landowners.store'), [
                'first_name' => 'Maria', 'last_name' => 'Santos',
            ])->assertForbidden();
        }
        $this->assertDatabaseCount('landowners', 0);
    }
}
