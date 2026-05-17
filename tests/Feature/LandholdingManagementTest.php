<?php

namespace Tests\Feature;

use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandholdingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_landowner_details_with_computed_hectares(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $landowner = Landowner::create([
            'first_name' => 'Computed',
            'last_name' => 'Owner',
            'municipality' => 'Dumaguete City',
            'province' => 'Negros Oriental',
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'LH-COMPUTED-001',
            'municipality' => 'Dumaguete City',
            'area_hectares' => 2.2500,
            'status' => 'active',
        ]);

        Landholding::create([
            'landowner_id' => $landowner->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 2.2500,
            'status' => Landholding::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.records.landowners.show', $landowner));

        $response->assertOk();
        $response->assertSee('Computed Owner');
        $response->assertSee('2.2500 ha');
        $response->assertSee('Computed Hectares Only');
    }

    public function test_staff_can_add_landholding_and_current_hectares_are_computed_from_active_records(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $landowner = Landowner::create([
            'first_name' => 'Active',
            'last_name' => 'Holder',
            'province' => 'Negros Oriental',
        ]);

        $activeParcel = Parcel::create([
            'parcel_code' => 'LH-ACTIVE-001',
            'area_hectares' => 1.5000,
            'status' => 'active',
        ]);

        $historicalParcel = Parcel::create([
            'parcel_code' => 'LH-HISTORICAL-001',
            'area_hectares' => 3.0000,
            'status' => 'active',
        ]);

        $this->actingAs($staffUser)
            ->post(route('staff.records.landowners.landholdings.store', $landowner), [
                'parcel_id' => $activeParcel->id,
                'area_hectares' => 1.5000,
                'status' => Landholding::STATUS_ACTIVE,
                'source_reference_number' => 'SRC-ACTIVE',
            ])
            ->assertRedirect();

        Landholding::create([
            'landowner_id' => $landowner->id,
            'parcel_id' => $historicalParcel->id,
            'area_hectares' => 3.0000,
            'status' => Landholding::STATUS_HISTORICAL,
        ]);

        $landowner->refresh();

        $this->assertSame(1.5, round($landowner->current_active_hectares, 4));
        $this->assertDatabaseHas('landholdings', [
            'landowner_id' => $landowner->id,
            'parcel_id' => $activeParcel->id,
            'status' => Landholding::STATUS_ACTIVE,
        ]);
    }
}
