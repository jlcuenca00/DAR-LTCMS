<?php

namespace Tests\Feature;

use App\Models\ApplicationDocument;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_application_index_page(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        LandTransferApplication::create([
            'application_code' => 'SEARCH-APP-001',
            'transferor_name' => 'Search Transferor',
            'transferee_name' => 'Search Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.applications.index'));

        $response->assertOk();
        $response->assertSee('Land Transfer Clearance Applications');
        $response->assertSee('Search and Filter Applications');
        $response->assertSee('SEARCH-APP-001');
        $response->assertSee('Search Transferor');
        $response->assertSee('Search Transferee');
    }

    public function test_staff_can_search_applications_by_code_or_party_name(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        LandTransferApplication::create([
            'application_code' => 'VISIBLE-SEARCH-001',
            'transferor_name' => 'Visible Transferor',
            'transferee_name' => 'Visible Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        LandTransferApplication::create([
            'application_code' => 'HIDDEN-SEARCH-001',
            'transferor_name' => 'Hidden Transferor',
            'transferee_name' => 'Hidden Transferee',
            'municipality' => 'Bayawan City',
            'barangay' => 'Villareal',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.applications.index', [
                'search' => 'Visible',
            ]));

        $response->assertOk();
        $response->assertSee('VISIBLE-SEARCH-001');
        $response->assertSee('Visible Transferor');
        $response->assertDontSee('HIDDEN-SEARCH-001');
        $response->assertDontSee('Hidden Transferor');
    }

    public function test_staff_can_filter_applications_by_status_and_location(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        LandTransferApplication::create([
            'application_code' => 'VISIBLE-LOCATION-001',
            'transferor_name' => 'Location Transferor',
            'transferee_name' => 'Location Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_REVIEW,
            'encoded_by' => $staffUser->id,
        ]);

        LandTransferApplication::create([
            'application_code' => 'HIDDEN-LOCATION-001',
            'transferor_name' => 'Other Transferor',
            'transferee_name' => 'Other Transferee',
            'municipality' => 'Bayawan City',
            'barangay' => 'Villareal',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.applications.index', [
                'status' => LandTransferApplication::STATUS_PENDING_REVIEW,
                'municipality' => 'Dumaguete City',
                'barangay' => 'Bantayan',
            ]));

        $response->assertOk();
        $response->assertSee('VISIBLE-LOCATION-001');
        $response->assertDontSee('HIDDEN-LOCATION-001');
    }

    public function test_staff_can_filter_applications_by_document_reference_number(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $visibleApplication = LandTransferApplication::create([
            'application_code' => 'VISIBLE-DOCREF-001',
            'transferor_name' => 'DocRef Transferor',
            'transferee_name' => 'DocRef Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        $hiddenApplication = LandTransferApplication::create([
            'application_code' => 'HIDDEN-DOCREF-001',
            'transferor_name' => 'Hidden DocRef Transferor',
            'transferee_name' => 'Hidden DocRef Transferee',
            'municipality' => 'Bayawan City',
            'barangay' => 'Villareal',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        $requiredDocument = RequiredDocument::forceCreate([
            'name' => 'Electronic Copy of Title',
            'applies_to' => 'transferor',
            'is_mandatory' => true,
            'legal_basis' => 'Search filter test basis',
        ]);

        ApplicationDocument::create([
            'land_transfer_application_id' => $visibleApplication->id,
            'required_document_id' => $requiredDocument->id,
            'file_path' => 'application-documents/test-visible.pdf',
            'original_filename' => 'test-visible.pdf',
            'uploaded_by' => $staffUser->id,
            'document_reference_number' => 'TCT-FILTER-123',
        ]);

        ApplicationDocument::create([
            'land_transfer_application_id' => $hiddenApplication->id,
            'required_document_id' => $requiredDocument->id,
            'file_path' => 'application-documents/test-hidden.pdf',
            'original_filename' => 'test-hidden.pdf',
            'uploaded_by' => $staffUser->id,
            'document_reference_number' => 'TCT-HIDDEN-999',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.applications.index', [
                'document_reference_number' => 'FILTER-123',
            ]));

        $response->assertOk();
        $response->assertSee('VISIBLE-DOCREF-001');
        $response->assertDontSee('HIDDEN-DOCREF-001');
    }

    public function test_landowner_cannot_view_staff_application_index_page(): void
    {
        $landownerUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        $response = $this->actingAs($landownerUser)
            ->get(route('staff.applications.index'));

        $response->assertForbidden();
    }

    public function test_geodetic_cannot_view_staff_application_index_page(): void
    {
        $geodeticUser = User::factory()->create([
            'role' => User::ROLE_GEODETIC,
            'is_active' => true,
        ]);

        $response = $this->actingAs($geodeticUser)
            ->get(route('staff.applications.index'));

        $response->assertForbidden();
    }
}