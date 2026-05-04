<?php

namespace Tests\Feature;

use App\Models\ApplicationDocument;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinalDecisionLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_application_rejects_document_upload(): void
    {
        Storage::fake('local');

        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'LOCK-UPLOAD-APPROVED-001',
            'transferor_name' => 'Locked Transferor',
            'transferee_name' => 'Locked Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => 'approved',
            'encoded_by' => $staffUser->id,
        ]);

        $requiredDocument = RequiredDocument::forceCreate([
            'name' => 'Locked Requirement',
            'applies_to' => 'transferor',
            'is_mandatory' => true,
            'legal_basis' => 'Test Basis',
        ]);

        $response = $this->actingAs($staffUser)->post(
            route('staff.applications.documents.store', [
                'application' => $application,
                'requiredDocument' => $requiredDocument,
            ]),
            [
                'file' => UploadedFile::fake()->create('locked.pdf', 100, 'application/pdf'),
                'annex_reference' => 'Annex Test',
                'remarks' => 'Should not upload',
            ]
        );

        $response->assertRedirect(route('staff.applications.show', $application));
        $response->assertSessionHas('error', 'This application is already finalized. Document uploads are locked.');

        $this->assertDatabaseMissing('application_documents', [
            'land_transfer_application_id' => $application->id,
            'required_document_id' => $requiredDocument->id,
        ]);
    }

    public function test_not_approved_application_rejects_document_upload(): void
    {
        Storage::fake('local');

        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'LOCK-UPLOAD-NOT-APPROVED-001',
            'transferor_name' => 'Locked Transferor',
            'transferee_name' => 'Locked Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => 'not_approved',
            'encoded_by' => $staffUser->id,
        ]);

        $requiredDocument = RequiredDocument::forceCreate([
            'name' => 'Locked Requirement',
            'applies_to' => 'transferee',
            'is_mandatory' => true,
            'legal_basis' => 'Test Basis',
        ]);

        $response = $this->actingAs($staffUser)->post(
            route('staff.applications.documents.store', [
                'application' => $application,
                'requiredDocument' => $requiredDocument,
            ]),
            [
                'file' => UploadedFile::fake()->create('locked.pdf', 100, 'application/pdf'),
                'annex_reference' => 'Annex Test',
                'remarks' => 'Should not upload',
            ]
        );

        $response->assertRedirect(route('staff.applications.show', $application));
        $response->assertSessionHas('error', 'This application is already finalized. Document uploads are locked.');

        $this->assertDatabaseMissing('application_documents', [
            'land_transfer_application_id' => $application->id,
            'required_document_id' => $requiredDocument->id,
        ]);
    }

    public function test_finalized_application_rejects_document_deletion(): void
    {
        Storage::fake('local');

        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'LOCK-DELETE-001',
            'transferor_name' => 'Locked Transferor',
            'transferee_name' => 'Locked Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => 'approved',
            'encoded_by' => $staffUser->id,
        ]);

        $requiredDocument = RequiredDocument::forceCreate([
            'name' => 'Locked Requirement',
            'applies_to' => 'transferor',
            'is_mandatory' => true,
            'legal_basis' => 'Test Basis',
        ]);

        Storage::put('application-documents/test-existing.pdf', 'test file content');

        $document = ApplicationDocument::create([
            'land_transfer_application_id' => $application->id,
            'required_document_id' => $requiredDocument->id,
            'original_filename' => 'test-existing.pdf',
            'file_path' => 'application-documents/test-existing.pdf',
            'annex_reference' => 'Annex Existing',
            'remarks' => 'Existing locked document',
            'uploaded_by' => $staffUser->id,
        ]);

        $response = $this->actingAs($staffUser)->delete(
            route('staff.applications.documents.destroy', [
                'application' => $application,
                'requiredDocument' => $requiredDocument,
            ])
        );

        $response->assertRedirect(route('staff.applications.show', $application));
        $response->assertSessionHas('error', 'This application is finalized. Documents cannot be removed.');

        $this->assertDatabaseHas('application_documents', [
            'id' => $document->id,
            'land_transfer_application_id' => $application->id,
            'required_document_id' => $requiredDocument->id,
        ]);

        Storage::assertExists('application-documents/test-existing.pdf');
    }

    public function test_finalized_application_rejects_workflow_actions(): void
    {
        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'LOCK-WORKFLOW-001',
            'transferor_name' => 'Locked Transferor',
            'transferee_name' => 'Locked Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => 'approved',
            'encoded_by' => $staffUser->id,
        ]);

        $this->actingAs($staffUser)
            ->post(route('staff.applications.submit', $application))
            ->assertSessionHasErrors('status');

        $this->actingAs($staffUser)
            ->post(route('staff.applications.approve', $application))
            ->assertSessionHasErrors('status');

        $this->actingAs($staffUser)
            ->post(route('staff.applications.not_approved', $application))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('land_transfer_applications', [
            'id' => $application->id,
            'status' => 'approved',
        ]);
    }

    public function test_model_correctly_identifies_finalized_applications(): void
    {
        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $draftApplication = LandTransferApplication::create([
            'application_code' => 'LOCK-MODEL-DRAFT-001',
            'transferor_name' => 'Draft Transferor',
            'transferee_name' => 'Draft Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => 'draft',
            'encoded_by' => $staffUser->id,
        ]);

        $approvedApplication = LandTransferApplication::create([
            'application_code' => 'LOCK-MODEL-APPROVED-001',
            'transferor_name' => 'Approved Transferor',
            'transferee_name' => 'Approved Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => 'approved',
            'encoded_by' => $staffUser->id,
        ]);

        $notApprovedApplication = LandTransferApplication::create([
            'application_code' => 'LOCK-MODEL-NOT-APPROVED-001',
            'transferor_name' => 'Not Approved Transferor',
            'transferee_name' => 'Not Approved Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => 'not_approved',
            'encoded_by' => $staffUser->id,
        ]);

        $this->assertFalse($draftApplication->isFinalized());
        $this->assertTrue($draftApplication->isEditable());

        $this->assertTrue($approvedApplication->isFinalized());
        $this->assertFalse($approvedApplication->isEditable());

        $this->assertTrue($notApprovedApplication->isFinalized());
        $this->assertFalse($notApprovedApplication->isEditable());
    }
}