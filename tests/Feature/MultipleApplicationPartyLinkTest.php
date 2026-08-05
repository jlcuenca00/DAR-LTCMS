<?php

namespace Tests\Feature;

use App\Models\ApplicationParcel;
use App\Models\LandTransferApplication;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\User;
use App\Services\LandholdingAreaValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultipleApplicationPartyLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_application_party_creates_and_links_a_separate_landowner_record(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'MULTI-PARTY-001',
            'transferor_name' => 'Juan Dela Cruz; Maria Dela Cruz',
            'transferors' => [
                ['name' => 'Juan Dela Cruz', 'landowner_id' => null],
                ['name' => 'Maria Dela Cruz', 'landowner_id' => null],
            ],
            'transferee_name' => 'Pedro Santos',
            'transferees' => [
                ['name' => 'Pedro Santos', 'landowner_id' => null],
            ],
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        $this->actingAs($staff)
            ->post(route('staff.applications.landowner-records.create', $application), [
                'party' => 'transferor',
                'index' => 0,
            ])
            ->assertRedirect();

        $this->actingAs($staff)
            ->post(route('staff.applications.landowner-records.create', $application), [
                'party' => 'transferor',
                'index' => 1,
            ])
            ->assertRedirect();

        $application->refresh();
        $transferors = $application->partyRows('transferor');

        $this->assertCount(2, $transferors);
        $this->assertNotNull($transferors[0]['landowner_id']);
        $this->assertNotNull($transferors[1]['landowner_id']);
        $this->assertNotSame($transferors[0]['landowner_id'], $transferors[1]['landowner_id']);

        $this->assertDatabaseHas('landowners', [
            'id' => $transferors[0]['landowner_id'],
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
        ]);

        $this->assertDatabaseHas('landowners', [
            'id' => $transferors[1]['landowner_id'],
            'first_name' => 'Maria',
            'last_name' => 'Cruz',
        ]);
    }

    public function test_equal_co_owner_shares_sync_to_separate_landholding_records(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $ownerA = Landowner::create([
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
            'province' => 'Negros Oriental',
        ]);

        $ownerB = Landowner::create([
            'first_name' => 'Maria',
            'last_name' => 'Cruz',
            'province' => 'Negros Oriental',
        ]);

        $transferee = Landowner::create([
            'first_name' => 'Pedro',
            'last_name' => 'Santos',
            'province' => 'Negros Oriental',
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'CO-OWNED-PARCEL-001',
            'province' => 'Negros Oriental',
            'area_hectares' => 4.0000,
            'status' => 'active',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'CO-OWNER-SYNC-001',
            'transferor_name' => 'Juan Cruz; Maria Cruz',
            'transferors' => [
                ['name' => 'Juan Cruz', 'landowner_id' => null],
                ['name' => 'Maria Cruz', 'landowner_id' => null],
            ],
            'transferee_name' => 'Pedro Santos',
            'transferees' => [
                ['name' => 'Pedro Santos', 'landowner_id' => null],
            ],
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        $applicationParcel = ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 4.0000,
            'parcel_code' => $parcel->parcel_code,
        ]);

        $this->actingAs($staff)
            ->patch(route('staff.applications.landowner-links.update', $application), [
                'transferors' => [
                    ['name' => 'Juan Cruz', 'landowner_id' => $ownerA->id],
                    ['name' => 'Maria Cruz', 'landowner_id' => $ownerB->id],
                ],
                'transferees' => [
                    ['name' => 'Pedro Santos', 'landowner_id' => $transferee->id],
                ],
                'split_equally' => 1,
                'sync_current_landholdings' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('landholdings', [
            'landowner_id' => $ownerA->id,
            'parcel_id' => $parcel->id,
            'status' => Landholding::STATUS_ACTIVE,
            'source_application_id' => $application->id,
        ]);

        $this->assertDatabaseHas('landholdings', [
            'landowner_id' => $ownerB->id,
            'parcel_id' => $parcel->id,
            'status' => Landholding::STATUS_ACTIVE,
            'source_application_id' => $application->id,
        ]);

        $holdingA = Landholding::query()
            ->where('landowner_id', $ownerA->id)
            ->where('parcel_id', $parcel->id)
            ->firstOrFail();
        $holdingB = Landholding::query()
            ->where('landowner_id', $ownerB->id)
            ->where('parcel_id', $parcel->id)
            ->firstOrFail();

        $this->assertSame(2.0, (float) $holdingA->area_hectares);
        $this->assertSame(2.0, (float) $holdingB->area_hectares);

        $application->refresh();
        $this->assertSame(2.0, (float) data_get($application->partyRows('transferor'), '0.parcel_shares.' . $applicationParcel->id));
        $this->assertSame(2.0, (float) data_get($application->partyRows('transferor'), '1.parcel_shares.' . $applicationParcel->id));
        $this->assertSame(2.0, $ownerA->fresh()->current_active_hectares);
        $this->assertSame(2.0, $ownerB->fresh()->current_active_hectares);
    }

    public function test_five_hectare_validation_calculates_each_linked_transferee_share_separately(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $transferor = Landowner::create([
            'first_name' => 'Current',
            'last_name' => 'Owner',
            'province' => 'Negros Oriental',
        ]);
        $transfereeA = Landowner::create([
            'first_name' => 'First',
            'last_name' => 'Recipient',
            'province' => 'Negros Oriental',
        ]);
        $transfereeB = Landowner::create([
            'first_name' => 'Second',
            'last_name' => 'Recipient',
            'province' => 'Negros Oriental',
        ]);
        $parcel = Parcel::create([
            'parcel_code' => 'MULTI-VALIDATION-PARCEL',
            'province' => 'Negros Oriental',
            'area_hectares' => 4.0000,
            'status' => 'active',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'MULTI-VALIDATION-001',
            'transferor_name' => 'Current Owner',
            'transferors' => [
                ['name' => 'Current Owner', 'landowner_id' => $transferor->id],
            ],
            'transferee_name' => 'First Recipient; Second Recipient',
            'transferees' => [
                ['name' => 'First Recipient', 'landowner_id' => $transfereeA->id],
                ['name' => 'Second Recipient', 'landowner_id' => $transfereeB->id],
            ],
            'transferor_landowner_id' => $transferor->id,
            'transferee_landowner_id' => $transfereeA->id,
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        $applicationParcel = ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 4.0000,
            'parcel_code' => $parcel->parcel_code,
        ]);

        $application->forceFill([
            'transferees' => [
                [
                    'name' => 'First Recipient',
                    'landowner_id' => $transfereeA->id,
                    'parcel_shares' => [(string) $applicationParcel->id => 1.5000],
                ],
                [
                    'name' => 'Second Recipient',
                    'landowner_id' => $transfereeB->id,
                    'parcel_shares' => [(string) $applicationParcel->id => 2.5000],
                ],
            ],
        ])->save();

        $result = app(LandholdingAreaValidationService::class)->forApplication($application->fresh());
        $rows = collect($result['per_landowner'])->keyBy('landowner_id');

        $this->assertCount(2, $rows);
        $this->assertSame(1.5, (float) $rows[$transfereeA->id]['this_application_total']);
        $this->assertSame(2.5, (float) $rows[$transfereeB->id]['this_application_total']);
    }

    public function test_secondary_linked_landowner_can_view_their_application_but_not_unrelated_applications(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $primaryUser = User::factory()->create(['role' => User::ROLE_LANDOWNER, 'is_active' => true]);
        $secondaryUser = User::factory()->create(['role' => User::ROLE_LANDOWNER, 'is_active' => true]);
        $otherUser = User::factory()->create(['role' => User::ROLE_LANDOWNER, 'is_active' => true]);

        $primaryOwner = Landowner::create([
            'user_id' => $primaryUser->id,
            'first_name' => 'Primary',
            'last_name' => 'Owner',
            'province' => 'Negros Oriental',
        ]);

        $secondaryOwner = Landowner::create([
            'user_id' => $secondaryUser->id,
            'first_name' => 'Secondary',
            'last_name' => 'Owner',
            'province' => 'Negros Oriental',
        ]);

        $otherOwner = Landowner::create([
            'user_id' => $otherUser->id,
            'first_name' => 'Other',
            'last_name' => 'Owner',
            'province' => 'Negros Oriental',
        ]);

        LandTransferApplication::create([
            'application_code' => 'VISIBLE-TO-SECONDARY',
            'transferor_name' => 'Primary Owner; Secondary Owner',
            'transferors' => [
                ['name' => 'Primary Owner', 'landowner_id' => $primaryOwner->id],
                ['name' => 'Secondary Owner', 'landowner_id' => $secondaryOwner->id],
            ],
            'transferee_name' => 'Other Owner',
            'transferees' => [
                ['name' => 'Other Owner', 'landowner_id' => $otherOwner->id],
            ],
            'transferor_landowner_id' => $primaryOwner->id,
            'transferee_landowner_id' => $otherOwner->id,
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        LandTransferApplication::create([
            'application_code' => 'UNRELATED-APPLICATION',
            'transferor_name' => 'Other Owner',
            'transferee_name' => 'Primary Owner',
            'transferor_landowner_id' => $otherOwner->id,
            'transferee_landowner_id' => $primaryOwner->id,
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        $response = $this->actingAs($secondaryUser)
            ->get(route('landowner.applications.index'));

        $response->assertOk();
        $response->assertSee('VISIBLE-TO-SECONDARY');
        $response->assertDontSee('UNRELATED-APPLICATION');

        $this->actingAs($staff)
            ->get(route('staff.records.landowners.show', $secondaryOwner))
            ->assertOk()
            ->assertSee('VISIBLE-TO-SECONDARY')
            ->assertDontSee('UNRELATED-APPLICATION');
    }

    public function test_finalized_application_rejects_party_link_and_share_changes(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $owner = Landowner::create([
            'first_name' => 'Locked',
            'last_name' => 'Owner',
            'province' => 'Negros Oriental',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'FINAL-PARTY-LINK-LOCK',
            'transferor_name' => 'Locked Owner',
            'transferors' => [
                ['name' => 'Locked Owner', 'landowner_id' => $owner->id],
            ],
            'transferee_name' => 'Final Transferee',
            'transferees' => [
                ['name' => 'Final Transferee', 'landowner_id' => null],
            ],
            'transferor_landowner_id' => $owner->id,
            'status' => LandTransferApplication::STATUS_RELEASED,
            'encoded_by' => $staff->id,
        ]);

        $this->actingAs($staff)
            ->patch(route('staff.applications.landowner-links.update', $application), [
                'transferors' => [
                    ['name' => 'Locked Owner', 'landowner_id' => null],
                ],
                'transferees' => [
                    ['name' => 'Final Transferee', 'landowner_id' => null],
                ],
            ])
            ->assertSessionHas('error');

        $application->refresh();
        $this->assertSame($owner->id, (int) data_get($application->partyRows('transferor'), '0.landowner_id'));
    }
}
