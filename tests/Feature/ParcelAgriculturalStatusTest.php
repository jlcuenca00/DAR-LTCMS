<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParcelAgriculturalStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_update_defaults_parcel_to_private_agricultural_when_land_type_field_is_not_on_form_and_audit_log_is_created(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'AGR-STATUS-001',
            'title_no' => 'TCT-AGR-001',
            'tax_decl_no' => 'TD-AGR-001',
            'province' => 'Negros Oriental',
            'municipality' => 'Bayawan City',
            'barangay' => 'Banga',
            'area_hectares' => 2.4000,
            'status' => 'active',
            'agricultural_status' => 'not_yet_determined',
            'remarks' => 'Initial agricultural classification test parcel.',
        ]);

        $response = $this->actingAs($staffUser)
            ->patch(route('staff.records.parcels.update', $parcel), [
                'parcel_code' => 'AGR-STATUS-001',
                'title_no' => 'TCT-AGR-001',
                'tax_decl_no' => 'TD-AGR-001',
                'province' => 'Negros Oriental',
                'municipality' => 'Bayawan City',
                'barangay' => 'Banga',
                'area_hectares' => '2.4000',
                'status' => 'active',
                'remarks' => 'Updated parcel without visible land type selector.',
            ]);

        $response->assertRedirect(route('staff.records.parcels.show', $parcel));

        $this->assertDatabaseHas('parcels', [
            'id' => $parcel->id,
            'agricultural_status' => 'private_agricultural',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staffUser->id,
            'auditable_type' => Parcel::class,
            'auditable_id' => $parcel->id,
            'action' => 'parcel_agricultural_status_updated',
        ]);

        $auditLog = AuditLog::query()
            ->where('action', 'parcel_agricultural_status_updated')
            ->firstOrFail();

        $this->assertSame('not_yet_determined', $auditLog->metadata['old_agricultural_status']);
        $this->assertSame('private_agricultural', $auditLog->metadata['new_agricultural_status']);
    }

    public function test_staff_parcel_details_display_agricultural_status_label(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'AGR-DETAILS-001',
            'title_no' => 'TCT-AGR-DETAILS-001',
            'province' => 'Negros Oriental',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'area_hectares' => 1.2500,
            'status' => 'active',
            'agricultural_status' => 'private_agricultural',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.records.parcels.show', $parcel));

        $response->assertOk();
        $response->assertSee('Agricultural Status');
        $response->assertSee('Private Agricultural Land');
    }

    public function test_staff_can_filter_parcels_by_agricultural_status(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        Parcel::create([
            'parcel_code' => 'VISIBLE-AGRI-FILTER',
            'title_no' => 'TCT-VISIBLE-AGRI',
            'province' => 'Negros Oriental',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'area_hectares' => 1.2500,
            'status' => 'active',
            'agricultural_status' => 'awarded_cloa',
        ]);

        Parcel::create([
            'parcel_code' => 'HIDDEN-AGRI-FILTER',
            'title_no' => 'TCT-HIDDEN-AGRI',
            'province' => 'Negros Oriental',
            'municipality' => 'Bayawan City',
            'barangay' => 'Banga',
            'area_hectares' => 2.0000,
            'status' => 'active',
            'agricultural_status' => 'private_agricultural',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.records.parcels.index', [
                'agricultural_status' => 'awarded_cloa',
            ]));

        $response->assertOk();
        $response->assertSee('VISIBLE-AGRI-FILTER');
        $response->assertDontSee('HIDDEN-AGRI-FILTER');
    }
}
