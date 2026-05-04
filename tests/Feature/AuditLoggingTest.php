<?php

namespace Tests\Feature;

use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_submission_creates_audit_log(): void
    {
        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'AUDIT-SUBMIT-001',
            'transferor_name' => 'Audit Transferor',
            'transferee_name' => 'Audit Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        $this->actingAs($staffUser)
            ->post(route('staff.applications.submit', $application))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staffUser->id,
            'land_transfer_application_id' => $application->id,
            'auditable_type' => LandTransferApplication::class,
            'auditable_id' => $application->id,
            'action' => 'application_submitted',
        ]);

        $log = AuditLog::where('action', 'application_submitted')->first();

        $this->assertSame(LandTransferApplication::STATUS_DRAFT, $log->metadata['old_status']);
        $this->assertSame(LandTransferApplication::STATUS_PENDING_REVIEW, $log->metadata['new_status']);
    }

    public function test_document_upload_creates_audit_log(): void
    {
        Storage::fake('local');

        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'AUDIT-DOC-UPLOAD-001',
            'transferor_name' => 'Audit Transferor',
            'transferee_name' => 'Audit Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        $requiredDocument = RequiredDocument::forceCreate([
            'name' => 'Audit Requirement',
            'applies_to' => 'transferor',
            'is_mandatory' => true,
            'legal_basis' => 'Audit Test Basis',
        ]);

        $this->actingAs($staffUser)->post(
            route('staff.applications.documents.store', [
                'application' => $application,
                'requiredDocument' => $requiredDocument,
            ]),
            [
                'file' => UploadedFile::fake()->create('audit-upload.pdf', 100, 'application/pdf'),
                'annex_reference' => 'Audit Annex',
                'remarks' => 'Audit upload test',
            ]
        )->assertSessionHas('success');

        $document = ApplicationDocument::where('land_transfer_application_id', $application->id)
            ->where('required_document_id', $requiredDocument->id)
            ->first();

        $this->assertNotNull($document);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staffUser->id,
            'land_transfer_application_id' => $application->id,
            'auditable_type' => ApplicationDocument::class,
            'auditable_id' => $document->id,
            'action' => 'document_uploaded',
        ]);

        $log = AuditLog::where('action', 'document_uploaded')->first();

        $this->assertSame($requiredDocument->id, $log->metadata['required_document_id']);
        $this->assertSame('Audit Requirement', $log->metadata['required_document_name']);
        $this->assertSame('audit-upload.pdf', $log->metadata['original_filename']);
    }

    public function test_document_removal_creates_audit_log(): void
    {
        Storage::fake('local');

        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'AUDIT-DOC-REMOVE-001',
            'transferor_name' => 'Audit Transferor',
            'transferee_name' => 'Audit Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        $requiredDocument = RequiredDocument::forceCreate([
            'name' => 'Audit Remove Requirement',
            'applies_to' => 'transferor',
            'is_mandatory' => false,
            'legal_basis' => 'Audit Test Basis',
        ]);

        Storage::put('application-documents/audit-existing.pdf', 'audit file content');

        $document = ApplicationDocument::create([
            'land_transfer_application_id' => $application->id,
            'required_document_id' => $requiredDocument->id,
            'original_filename' => 'audit-existing.pdf',
            'file_path' => 'application-documents/audit-existing.pdf',
            'annex_reference' => 'Audit Existing',
            'remarks' => 'Existing audit document',
            'uploaded_by' => $staffUser->id,
        ]);

        $this->actingAs($staffUser)->delete(
            route('staff.applications.documents.destroy', [
                'application' => $application,
                'requiredDocument' => $requiredDocument,
            ])
        )->assertSessionHas('success');

        $this->assertDatabaseMissing('application_documents', [
            'id' => $document->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staffUser->id,
            'land_transfer_application_id' => $application->id,
            'auditable_type' => ApplicationDocument::class,
            'auditable_id' => $document->id,
            'action' => 'document_removed',
        ]);

        $log = AuditLog::where('action', 'document_removed')->first();

        $this->assertSame($requiredDocument->id, $log->metadata['required_document_id']);
        $this->assertSame('Audit Remove Requirement', $log->metadata['required_document_name']);
        $this->assertSame('audit-existing.pdf', $log->metadata['original_filename']);
    }

    public function test_approval_creates_application_and_clearance_audit_logs(): void
    {
        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $transferor = Landowner::create([
            'first_name' => 'Audit',
            'last_name' => 'Transferor',
            'province' => 'Negros Oriental',
        ]);

        $transferee = Landowner::create([
            'first_name' => 'Audit',
            'last_name' => 'Transferee',
            'province' => 'Negros Oriental',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'AUDIT-APPROVE-001',
            'transferor_name' => 'Audit Transferor',
            'transferee_name' => 'Audit Transferee',
            'transferor_landowner_id' => $transferor->id,
            'transferee_landowner_id' => $transferee->id,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_REVIEW,
            'encoded_by' => $staffUser->id,
        ]);

        $this->actingAs($staffUser)->post(
            route('staff.applications.approve', $application),
            [
                'decision_reason' => 'Audit approval reason',
                'decision_notes' => 'Audit approval notes',
            ]
        )->assertSessionHas('success');

        $application->refresh();

        $this->assertSame(LandTransferApplication::STATUS_APPROVED, $application->status);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staffUser->id,
            'land_transfer_application_id' => $application->id,
            'auditable_type' => LandTransferApplication::class,
            'auditable_id' => $application->id,
            'action' => 'application_approved',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staffUser->id,
            'land_transfer_application_id' => $application->id,
            'action' => 'clearance_generated',
        ]);

        $approvalLog = AuditLog::where('action', 'application_approved')->first();

        $this->assertSame('Audit approval reason', $approvalLog->metadata['decision_reason']);
        $this->assertSame('Audit approval notes', $approvalLog->metadata['decision_notes']);
        $this->assertFalse($approvalLog->metadata['registry_mutation_performed']);

        $clearanceLog = AuditLog::where('action', 'clearance_generated')->first();

        $this->assertSame(LandTransferApplication::STATUS_APPROVED, $clearanceLog->metadata['decision_status']);
        $this->assertSame(0, $clearanceLog->metadata['parcel_count']);
    }
}