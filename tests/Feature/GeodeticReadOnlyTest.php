<?php

namespace Tests\Feature;

use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeodeticReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_geodetic_user_can_view_read_only_parcel_reference_page(): void
    {
        $geodeticUser = User::factory()->create([
            'role' => 'geodetic',
        ]);

        $landowner = Landowner::create([
            'first_name' => 'Geo',
            'last_name' => 'Reference',
            'province' => 'Negros Oriental',
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'GEO-PARCEL-001',
            'title_no' => 'GEO-TITLE-001',
            'tax_decl_no' => 'GEO-TD-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.2500,
            'status' => 'active',
        ]);

        Landholding::create([
            'landowner_id' => $landowner->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 1.2500,
            'status' => 'active',
        ]);

        $response = $this->actingAs($geodeticUser)
            ->get(route('geodetic.parcels.index'));

        $response->assertOk();
        $response->assertSee('GEO-PARCEL-001');
        $response->assertSee('GEO-TITLE-001');
        $response->assertSee('Geo Reference');
    }

    public function test_geodetic_reference_queries_do_not_load_unneeded_landowner_or_application_fields(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $geodetic = User::factory()->create(['role' => User::ROLE_GEODETIC]);

        $landowner = Landowner::create([
            'first_name' => 'Least',
            'middle_name' => 'Privilege',
            'last_name' => 'Owner',
            'contact_number' => '09171234567',
            'address_line' => 'Sensitive address line',
            'province' => 'Negros Oriental',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'GEO-LIMITED-APP-001',
            'transferor_name' => 'Least Privilege Owner',
            'transferee_name' => 'Other Party',
            'decision_notes' => 'Internal legal decision notes must not be loaded for Geodetic reference display.',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'GEO-LIMITED-PARCEL-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.2500,
            'status' => 'active',
        ]);

        Landholding::create([
            'landowner_id' => $landowner->id,
            'parcel_id' => $parcel->id,
            'source_application_id' => $application->id,
            'area_hectares' => 1.2500,
            'status' => 'active',
        ]);

        $this->actingAs($geodetic)
            ->get(route('geodetic.parcels.index'))
            ->assertOk()
            ->assertViewHas('landholdings', function ($records) {
                $holding = $records->first();

                return $holding
                    && ! array_key_exists('contact_number', $holding->landowner->getAttributes())
                    && ! array_key_exists('address_line', $holding->landowner->getAttributes());
            });

        $this->actingAs($geodetic)
            ->get(route('geodetic.parcels.show', $parcel))
            ->assertOk()
            ->assertViewHas('parcel', function ($viewParcel) {
                $holding = $viewParcel->landholdings->first();

                return $holding
                    && ! array_key_exists('contact_number', $holding->landowner->getAttributes())
                    && ! array_key_exists('address_line', $holding->landowner->getAttributes())
                    && array_keys($holding->sourceApplication->getAttributes()) === ['id', 'application_code'];
            });
    }

    public function test_geodetic_user_cannot_access_clearance_application_reference_page(): void
    {
        $geodeticUser = User::factory()->create([
            'role' => 'geodetic',
        ]);

        $response = $this->actingAs($geodeticUser)
            ->get('/geodetic/applications');

        $response->assertNotFound();
    }

    public function test_geodetic_dashboard_does_not_show_clearance_application_links(): void
    {
        $geodeticUser = User::factory()->create([
            'role' => 'geodetic',
        ]);

        $response = $this->actingAs($geodeticUser)
            ->get(route('geodetic.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Clearance Applications');
        $response->assertDontSee('/geodetic/applications');
    }

    public function test_geodetic_user_cannot_access_staff_application_review_page(): void
    {
        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $geodeticUser = User::factory()->create([
            'role' => 'geodetic',
        ]);

        $application = \App\Models\LandTransferApplication::create([
            'application_code' => 'GEO-BLOCKED-001',
            'transferor_name' => 'Blocked Transferor',
            'transferee_name' => 'Blocked Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => 'pending_review',
            'encoded_by' => $staffUser->id,
        ]);

        $response = $this->actingAs($geodeticUser)
            ->get(route('staff.applications.show', $application));

        $response->assertForbidden();
    }

    public function test_geodetic_user_cannot_submit_approve_or_request_compliance(): void
    {
        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $geodeticUser = User::factory()->create([
            'role' => 'geodetic',
        ]);

        $application = \App\Models\LandTransferApplication::create([
            'application_code' => 'GEO-WORKFLOW-BLOCKED-001',
            'transferor_name' => 'Workflow Transferor',
            'transferee_name' => 'Workflow Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => 'pending_review',
            'encoded_by' => $staffUser->id,
        ]);

        $this->actingAs($geodeticUser)
            ->post(route('staff.applications.submit', $application))
            ->assertForbidden();

        $this->actingAs($geodeticUser)
            ->post(route('staff.applications.approve', $application))
            ->assertForbidden();

        $this->actingAs($geodeticUser)
            ->post(route('staff.applications.compliance.request', $application), [
                'category' => 'clarification_needed',
                'details' => 'Geodetic users must not request workflow compliance.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('land_transfer_applications', [
            'id' => $application->id,
            'status' => 'pending_review',
        ]);
    }

    public function test_geodetic_user_cannot_access_staff_clearance_routes(): void
    {
        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $geodeticUser = User::factory()->create([
            'role' => 'geodetic',
        ]);

        $application = \App\Models\LandTransferApplication::create([
            'application_code' => 'GEO-CLEARANCE-BLOCKED-001',
            'transferor_name' => 'Clearance Transferor',
            'transferee_name' => 'Clearance Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => 'approved',
            'encoded_by' => $staffUser->id,
        ]);

        $this->actingAs($geodeticUser)
            ->get(route('staff.applications.clearance.show', $application))
            ->assertForbidden();

        $this->actingAs($geodeticUser)
            ->get(route('staff.applications.clearance.pdf', $application))
            ->assertForbidden();
    }
}
