<?php

namespace Tests\Feature;

use App\Models\ApplicationDocument;
use App\Models\ApplicationParcel;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\RequiredDocument;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BetaSystemRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_encode_records_and_create_an_application_from_existing_parcel(): void
    {
        $staff = $this->staffUser();

        $this->actingAs($staff)
            ->post(route('staff.records.landowners.store'), [
                'first_name' => 'Beta',
                'middle_name' => 'Test',
                'last_name' => 'Transferor',
                'suffix' => null,
                'contact_number' => '09170000001',
                'address_line' => 'Poblacion',
                'barangay' => 'Banga',
                'municipality' => 'Bayawan City',
                'province' => 'Negros Oriental',
                'user_id' => null,
            ])
            ->assertSessionHas('success');

        $transferor = Landowner::where('last_name', 'Transferor')->firstOrFail();
        $transferee = $this->landownerRecord('Beta', 'Transferee');

        $this->actingAs($staff)
            ->post(route('staff.records.parcels.store'), [
                'parcel_code' => 'BETA-PARCEL-001',
                'title_no' => 'T-BETA-001',
                'tax_decl_no' => 'TD-BETA-001',
                'lot_number' => 'LOT-BETA-001',
                'survey_plan_number' => 'PSD-BETA-001',
                'title_type' => 'tct',
                'rod_office' => 'Negros Oriental Province',
                'province' => 'Negros Oriental',
                'municipality' => 'Bayawan City',
                'barangay' => 'Banga',
                'area_hectares' => '1.2500',
                'area_square_meters' => '12500',
                'status' => 'active',
                'remarks' => 'Beta regression parcel.',
            ])
            ->assertSessionHas('success');

        $parcel = Parcel::where('parcel_code', 'BETA-PARCEL-001')->firstOrFail();

        $this->assertSame(Parcel::DEFAULT_AGRICULTURAL_STATUS, $parcel->agricultural_status);

        $this->actingAs($staff)
            ->post(route('staff.records.landowners.landholdings.store', $transferor), [
                'parcel_id' => $parcel->id,
                'area_hectares' => '1.2500',
                'status' => Landholding::STATUS_ACTIVE,
                'source_reference_number' => 'SRC-BETA-001',
                'remarks' => 'Active landholding for beta workflow.',
            ])
            ->assertSessionHas('success');

        $this->actingAs($staff)
            ->post(route('staff.applications.store'), [
                'transferor_landowner_id' => $transferor->id,
                'transferee_landowner_id' => $transferee->id,
                'applicant_type' => 'transferor',
                'transferor_name' => $transferor->full_name,
                'transferee_name' => $transferee->full_name,
                'municipality' => 'Bayawan City',
                'barangay' => 'Banga',
                'transfer_nature' => 'sale',
                'parcel_id' => $parcel->id,
                'area_hectares' => '1.2500',
            ])
            ->assertSessionHas('success');

        $application = LandTransferApplication::latest('id')->firstOrFail();

        $this->assertSame(LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW, $application->status);

        $this->assertDatabaseHas('landholdings', [
            'landowner_id' => $transferor->id,
            'parcel_id' => $parcel->id,
            'status' => Landholding::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('application_parcels', [
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => 'BETA-PARCEL-001',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staff->id,
            'action' => 'application_created',
            'land_transfer_application_id' => $application->id,
        ]);

        $this->assertDatabaseHas('system_notifications', [
            'user_id' => $staff->id,
            'type' => 'application_created',
            'related_type' => LandTransferApplication::class,
            'related_id' => $application->id,
        ]);
    }

    public function test_landholding_creation_requires_an_existing_parcel_record(): void
    {
        $staff = $this->staffUser();
        $landowner = $this->landownerRecord('Parcel Required', 'Owner');

        $this->actingAs($staff)
            ->post(route('staff.records.landowners.landholdings.store', $landowner), [
                'parcel_id' => null,
                'area_hectares' => '1.0000',
                'status' => Landholding::STATUS_ACTIVE,
            ])
            ->assertSessionHasErrors('parcel_id');

        $this->actingAs($staff)
            ->post(route('staff.records.landowners.landholdings.store', $landowner), [
                'parcel_id' => 999999,
                'area_hectares' => '1.0000',
                'status' => Landholding::STATUS_ACTIVE,
            ])
            ->assertSessionHasErrors('parcel_id');

        $this->assertDatabaseCount('landholdings', 0);
    }

    public function test_office_workflow_reaches_release_and_generates_clearance_record(): void
    {
        $staff = $this->staffUser();
        [$transferor, $transferee, $parcel, $landholding, $application] = $this->applicationPackage($staff);

        foreach ([
            LandTransferApplication::STATUS_ENDORSED_LTI,
            LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL,
            LandTransferApplication::STATUS_ENDORSED_PARPO,
            LandTransferApplication::STATUS_FOR_RELEASING,
        ] as $expectedStatus) {
            $this->actingAs($staff)
                ->post(route('staff.applications.submit', $application))
                ->assertSessionHas('success');

            $application->refresh();
            $this->assertSame($expectedStatus, $application->status);
        }

        $this->actingAs($staff)
            ->post(route('staff.applications.approve', $application), [
                'decision_reason' => 'Clearance release validated for beta regression testing.',
                'decision_notes' => 'Final release path verified.',
            ])
            ->assertSessionHas('success');

        $application->refresh();
        $landholding->refresh();

        $this->assertSame(LandTransferApplication::STATUS_RELEASED, $application->status);
        $this->assertSame($staff->id, $application->reviewed_by);
        $this->assertNotNull($application->validated_at);
        $this->assertNull($application->registry_mutated_at);
        $this->assertNull($application->registry_mutated_by);
        $this->assertNotNull($application->clearance()->first());

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staff->id,
            'action' => 'application_released',
            'land_transfer_application_id' => $application->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staff->id,
            'action' => 'clearance_generated',
            'land_transfer_application_id' => $application->id,
        ]);

        $this->assertDatabaseHas('landholdings', [
            'id' => $landholding->id,
            'landowner_id' => $transferor->id,
            'parcel_id' => $parcel->id,
            'status' => Landholding::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseMissing('landholdings', [
            'landowner_id' => $transferee->id,
            'parcel_id' => $parcel->id,
            'source_application_id' => $application->id,
        ]);

        $this->assertDatabaseHas('system_notifications', [
            'user_id' => $staff->id,
            'type' => 'application_released',
            'related_type' => LandTransferApplication::class,
            'related_id' => $application->id,
        ]);
    }

    public function test_denial_path_records_final_decision_and_preserves_landholding_records(): void
    {
        $staff = $this->staffUser();
        [$transferor, $transferee, $parcel, $landholding, $application] = $this->applicationPackage($staff, 'BETA-DENIED-001');

        $this->actingAs($staff)
            ->post(route('staff.applications.not_approved', $application), [
                'decision_reason' => 'Documentary review did not support release.',
                'decision_notes' => 'Beta denial path verified.',
            ])
            ->assertSessionHas('success');

        $application->refresh();
        $landholding->refresh();

        $this->assertSame(LandTransferApplication::STATUS_DENIED, $application->status);
        $this->assertSame($staff->id, $application->reviewed_by);
        $this->assertNotNull($application->validated_at);
        $this->assertNull($application->registry_mutated_at);
        $this->assertNull($application->registry_mutated_by);
        $this->assertNotNull($application->clearance()->first());

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staff->id,
            'action' => 'application_denied',
            'land_transfer_application_id' => $application->id,
        ]);

        $this->assertDatabaseHas('landholdings', [
            'id' => $landholding->id,
            'landowner_id' => $transferor->id,
            'parcel_id' => $parcel->id,
            'status' => Landholding::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseMissing('landholdings', [
            'landowner_id' => $transferee->id,
            'parcel_id' => $parcel->id,
            'source_application_id' => $application->id,
        ]);

        $this->assertDatabaseHas('system_notifications', [
            'user_id' => $staff->id,
            'type' => 'application_denied',
            'related_type' => LandTransferApplication::class,
            'related_id' => $application->id,
        ]);
    }

    public function test_final_decision_states_lock_document_upload_and_form4_review(): void
    {
        Storage::fake('local');

        $staff = $this->staffUser();
        $requiredDocument = RequiredDocument::forceCreate([
            'name' => 'Beta Final Lock Requirement',
            'applies_to' => 'transferor',
            'is_mandatory' => true,
            'requirement_classification' => RequiredDocument::CLASSIFICATION_MANDATORY,
            'blocks_acceptance' => true,
            'legal_basis' => 'Beta regression test',
        ]);

        foreach ([
            LandTransferApplication::STATUS_RELEASED,
            LandTransferApplication::STATUS_DENIED,
        ] as $status) {
            $application = LandTransferApplication::create([
                'application_code' => 'BETA-FINAL-' . strtoupper($status),
                'transferor_name' => 'Locked Transferor',
                'transferee_name' => 'Locked Transferee',
                'municipality' => 'Dumaguete City',
                'barangay' => 'Bantayan',
                'status' => $status,
                'encoded_by' => $staff->id,
            ]);

            $this->actingAs($staff)
                ->post(route('staff.applications.documents.store', [$application, $requiredDocument]), [
                    'file' => UploadedFile::fake()->create('locked.pdf', 100, 'application/pdf'),
                    'annex_reference' => 'Annex Lock',
                    'remarks' => 'Should stay locked.',
                ])
                ->assertSessionHas('error');

            $this->assertDatabaseMissing('application_documents', [
                'land_transfer_application_id' => $application->id,
                'required_document_id' => $requiredDocument->id,
            ]);

            $this->actingAs($staff)
                ->patch(route('staff.applications.form4.update', $application), [
                    'ltc_form4_recommendation_decision' => 'approval',
                    'ltc_form4_certifying_officer_name' => 'Locked Officer',
                ])
                ->assertSessionHas('error');

            $application->refresh();
            $this->assertNull($application->ltc_form4_recommendation_decision);
            $this->assertNull($application->ltc_form4_certifying_officer_name);
        }
    }

    public function test_role_access_boundaries_for_application_and_record_pages(): void
    {
        $staff = $this->staffUser();
        $geodetic = User::factory()->create(['role' => User::ROLE_GEODETIC, 'is_active' => true]);
        $landownerUser = User::factory()->create(['role' => User::ROLE_LANDOWNER, 'is_active' => true]);
        $otherLandownerUser = User::factory()->create(['role' => User::ROLE_LANDOWNER, 'is_active' => true]);

        $landowner = $this->landownerRecord('Visible', 'Owner', $landownerUser);
        $otherLandowner = $this->landownerRecord('Hidden', 'Owner', $otherLandownerUser);

        $visibleParcel = $this->parcelRecord('BETA-VISIBLE-PARCEL');
        $hiddenParcel = $this->parcelRecord('BETA-HIDDEN-PARCEL');

        Landholding::create([
            'landowner_id' => $landowner->id,
            'parcel_id' => $visibleParcel->id,
            'area_hectares' => 1.0000,
            'status' => Landholding::STATUS_ACTIVE,
        ]);

        Landholding::create([
            'landowner_id' => $otherLandowner->id,
            'parcel_id' => $hiddenParcel->id,
            'area_hectares' => 1.0000,
            'status' => Landholding::STATUS_ACTIVE,
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'BETA-RBAC-001',
            'transferor_name' => $landowner->full_name,
            'transferee_name' => $otherLandowner->full_name,
            'transferor_landowner_id' => $landowner->id,
            'transferee_landowner_id' => $otherLandowner->id,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        $this->actingAs($landownerUser)
            ->get(route('staff.applications.show', $application))
            ->assertForbidden();

        $this->actingAs($geodetic)
            ->post(route('staff.applications.approve', $application))
            ->assertForbidden();

        $this->actingAs($geodetic)
            ->post(route('staff.applications.not_approved', $application), [
                'decision_reason' => 'Should be blocked.',
            ])
            ->assertForbidden();

        $this->actingAs($landownerUser)
            ->get(route('landowner.parcels.show', $visibleParcel))
            ->assertOk();

        $this->actingAs($landownerUser)
            ->get(route('landowner.parcels.show', $hiddenParcel))
            ->assertForbidden();

        $this->actingAs($geodetic)
            ->get(route('geodetic.parcels.show', $hiddenParcel))
            ->assertOk();
    }

    public function test_required_document_deduplication_keeps_one_recent_tax_declaration_entry(): void
    {
        $oldRequirement = RequiredDocument::forceCreate([
            'name' => 'Recent Tax Declaration',
            'applies_to' => 'transferor',
            'is_mandatory' => false,
            'requirement_classification' => RequiredDocument::CLASSIFICATION_CASE_DEPENDENT,
            'blocks_acceptance' => false,
        ]);

        $preferredRequirement = RequiredDocument::forceCreate([
            'name' => 'Recent Tax Declaration (if available)',
            'applies_to' => 'transferor',
            'is_mandatory' => false,
            'requirement_classification' => RequiredDocument::CLASSIFICATION_CASE_DEPENDENT,
            'blocks_acceptance' => false,
        ]);

        $deduplicated = RequiredDocument::deduplicateForApplicationReview(
            RequiredDocument::whereIn('id', [$oldRequirement->id, $preferredRequirement->id])->get()
        );

        $this->assertTrue($deduplicated->contains('id', $preferredRequirement->id));
        $this->assertFalse($deduplicated->contains('id', $oldRequirement->id));
        $this->assertSame(
            1,
            $deduplicated
                ->filter(fn (RequiredDocument $document) => str_contains(
                    RequiredDocument::normalizedReviewName((string) $document->name),
                    'recent tax declaration'
                ))
                ->count()
        );
    }

    public function test_application_review_preview_contains_form_sections_without_duplicate_status_card(): void
    {
        $staff = $this->staffUser();
        [, , , , $application] = $this->applicationPackage($staff, 'BETA-PREVIEW-001');

        $response = $this->actingAs($staff)
            ->get(route('staff.applications.show', $application));

        $response->assertOk();
        $response->assertSee('LTC Form No. 3', false);
        $response->assertSee('LTC Form No. 4', false);
        $response->assertDontSee('Incomplete / with lacking documents', false);
    }

    public function test_printable_form_routes_are_available_to_staff(): void
    {
        $staff = $this->staffUser();
        [, , , , $application] = $this->applicationPackage($staff, 'BETA-PRINT-001');

        $this->actingAs($staff)
            ->get(route('staff.applications.acknowledgement.pdf', $application))
            ->assertOk();

        $this->actingAs($staff)
            ->get(route('staff.applications.form4.pdf', $application))
            ->assertOk();
    }

    private function staffUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);
    }

    private function landownerRecord(string $firstName, string $lastName, ?User $user = null): Landowner
    {
        return Landowner::create([
            'user_id' => $user?->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'province' => 'Negros Oriental',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
        ]);
    }

    private function parcelRecord(string $parcelCode = 'BETA-PARCEL-PACKAGE'): Parcel
    {
        return Parcel::create([
            'parcel_code' => $parcelCode,
            'title_no' => 'T-' . $parcelCode,
            'tax_decl_no' => 'TD-' . $parcelCode,
            'lot_number' => 'LOT-' . $parcelCode,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.2500,
            'area_square_meters' => 12500,
            'status' => 'active',
            'agricultural_status' => Parcel::DEFAULT_AGRICULTURAL_STATUS,
            'geometry_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [123.3000, 9.3000],
                    [123.3010, 9.3000],
                    [123.3010, 9.3010],
                    [123.3000, 9.3010],
                    [123.3000, 9.3000],
                ]],
            ],
        ]);
    }

    private function applicationPackage(User $staff, string $applicationCode = 'BETA-WORKFLOW-001'): array
    {
        $transferorUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        $transfereeUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        $transferor = $this->landownerRecord('Original', 'Owner', $transferorUser);
        $transferee = $this->landownerRecord('Proposed', 'Recipient', $transfereeUser);
        $parcel = $this->parcelRecord($applicationCode . '-PARCEL');

        $landholding = Landholding::create([
            'landowner_id' => $transferor->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 1.2500,
            'status' => Landholding::STATUS_ACTIVE,
            'remarks' => 'Existing monitored landholding.',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => $applicationCode,
            'applicant_name' => $transferor->full_name,
            'applicant_type' => 'transferor',
            'transferor_name' => $transferor->full_name,
            'transferee_name' => $transferee->full_name,
            'transferor_landowner_id' => $transferor->id,
            'transferee_landowner_id' => $transferee->id,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'transfer_nature' => 'sale',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'title_no' => $parcel->title_no,
            'tax_decl_no' => $parcel->tax_decl_no,
            'lot_number' => $parcel->lot_number,
            'area_hectares' => 1.2500,
            'area_square_meters' => 12500,
        ]);

        return [$transferor, $transferee, $parcel, $landholding, $application];
    }
}
