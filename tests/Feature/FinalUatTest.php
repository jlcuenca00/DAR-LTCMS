<?php

namespace Tests\Feature;

use App\Models\ApplicationClearance;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalUatTest extends TestCase
{
    use RefreshDatabase;

    public function test_landowner_application_portal_only_shows_records_linked_to_signed_in_landowner(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);
        $landownerUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);
        $otherUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        $landowner = Landowner::create([
            'user_id' => $landownerUser->id,
            'first_name' => 'UAT',
            'last_name' => 'Visible Owner',
            'province' => 'Negros Oriental',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
        ]);
        $otherLandowner = Landowner::create([
            'user_id' => $otherUser->id,
            'first_name' => 'UAT',
            'last_name' => 'Hidden Owner',
            'province' => 'Negros Oriental',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
        ]);

        $this->application($staff, 'UAT-OWN-TRANSFEROR', [
            'transferor_landowner_id' => $landowner->id,
            'transferor_name' => $landowner->full_name,
        ]);

        $this->application($staff, 'UAT-OWN-TRANSFEREE', [
            'transferee_landowner_id' => $landowner->id,
            'transferee_name' => $landowner->full_name,
        ]);

        $this->application($staff, 'UAT-OWN-JSON-LINK', [
            'transferors' => [[
                'name' => $landowner->full_name,
                'landowner_id' => $landowner->id,
                'parcel_shares' => [],
            ]],
            'transferor_name' => $landowner->full_name,
        ]);

        $hidden = $this->application($staff, 'UAT-OTHER-LANDOWNER', [
            'transferor_landowner_id' => $otherLandowner->id,
            'transferor_name' => $otherLandowner->full_name,
            'status' => LandTransferApplication::STATUS_RELEASED,
        ]);

        $response = $this->actingAs($landownerUser)
            ->get(route('landowner.applications.index'));

        $response->assertOk();
        $response->assertSee('UAT-OWN-TRANSFEROR');
        $response->assertSee('UAT-OWN-TRANSFEREE');
        $response->assertSee('UAT-OWN-JSON-LINK');
        $response->assertDontSee('UAT-OTHER-LANDOWNER');

        $this->actingAs($landownerUser)
            ->get(route('landowner.applications.clearance.show', $hidden))
            ->assertForbidden();

        $this->actingAs($landownerUser)
            ->get(route('landowner.applications.clearance.pdf', $hidden))
            ->assertForbidden();
    }

    public function test_landowner_decision_output_controls_follow_release_state(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);
        $landownerUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);
        $landowner = Landowner::create([
            'user_id' => $landownerUser->id,
            'first_name' => 'Release',
            'last_name' => 'Aware Owner',
            'province' => 'Negros Oriental',
        ]);

        $application = $this->application($staff, 'UAT-RELEASE-AWARE-001', [
            'transferor_landowner_id' => $landowner->id,
            'transferor_name' => $landowner->full_name,
            'status' => LandTransferApplication::STATUS_APPROVED,
            'release_status' => LandTransferApplication::RELEASE_NOT_READY,
            'decision_authority' => LandTransferApplication::FINAL_DECISION_AUTHORITY,
            'decision_officer_name' => 'PARPO II UAT Signatory',
            'decision_date' => now()->toDateString(),
        ]);

        ApplicationClearance::create([
            'land_transfer_application_id' => $application->id,
            'clearance_number' => '1803-2026-UAT (1)',
            'decision_status' => LandTransferApplication::STATUS_APPROVED,
            'decision_authority' => LandTransferApplication::FINAL_DECISION_AUTHORITY,
            'decision_officer_name' => 'PARPO II UAT Signatory',
            'decision_date' => now()->toDateString(),
            'application_code' => $application->application_code,
            'transferor_name' => $application->transferorDisplayName(),
            'transferee_name' => $application->transfereeDisplayName(),
            'municipality' => $application->municipality,
            'barangay' => $application->barangay,
            'total_area_hectares' => '0.0000',
            'parcel_snapshot' => [],
            'generated_by' => $staff->id,
            'generated_at' => now(),
        ]);

        $pending = $this->actingAs($landownerUser)
            ->get(route('landowner.applications.index'));

        $pending->assertOk();
        $pending->assertSee('Decision recorded — signed output pending');
        $pending->assertDontSee('View Decision Output');

        $this->actingAs($landownerUser)
            ->get(route('landowner.applications.clearance.show', $application))
            ->assertRedirect(route('landowner.applications.index'))
            ->assertSessionHas('error', 'The final decision has been recorded, but the signed output has not yet been released to the client.');

        $application->forceFill([
            'release_status' => LandTransferApplication::RELEASE_READY,
            'ready_for_release_at' => now(),
        ])->save();

        $ready = $this->actingAs($landownerUser)
            ->get(route('landowner.applications.index'));

        $ready->assertOk();
        $ready->assertSee('Ready for Release');
        $ready->assertDontSee('View Decision Output');

        $application->forceFill([
            'release_status' => LandTransferApplication::RELEASED_TO_CLIENT,
            'released_at' => now(),
            'released_by' => $staff->id,
        ])->save();

        $released = $this->actingAs($landownerUser)
            ->get(route('landowner.applications.index'));

        $released->assertOk();
        $released->assertSee('Released to Client');
        $released->assertSee('View Decision Output');

        $this->actingAs($landownerUser)
            ->get(route('landowner.applications.clearance.show', $application))
            ->assertOk()
            ->assertSee('PARPO II UAT Signatory');
    }

    public function test_landowner_cannot_create_or_change_clearance_applications_through_staff_routes(): void
    {
        $landownerUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        $this->actingAs($landownerUser)
            ->get(route('staff.applications.create'))
            ->assertForbidden();

        $this->actingAs($landownerUser)
            ->post(route('staff.applications.store'), [
                'transferor_name' => 'Unauthorized Transferor',
                'transferee_name' => 'Unauthorized Transferee',
                'municipality' => 'Dumaguete City',
                'barangay' => 'Bantayan',
                'transfer_nature' => 'sale',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('land_transfer_applications', [
            'transferor_name' => 'Unauthorized Transferor',
        ]);
    }

    private function application(User $staff, string $code, array $overrides = []): LandTransferApplication
    {
        return LandTransferApplication::create(array_merge([
            'application_code' => $code,
            'transferor_name' => 'UAT Transferor',
            'transferee_name' => 'UAT Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ], $overrides));
    }
}
