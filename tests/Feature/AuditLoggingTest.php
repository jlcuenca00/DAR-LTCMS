<?php

namespace Tests\Feature;

use App\Models\ApplicationDocument;
use App\Models\ApplicationParcel;
use App\Models\AuditLog;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\RequiredDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_stage_advancement_creates_audit_log(): void
    {
        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'AUDIT-ADVANCE-001',
            'transferor_name' => 'Audit Transferor',
            'transferee_name' => 'Audit Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
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
            'action' => 'application_status_advanced',
        ]);

        $log = AuditLog::where('action', 'application_status_advanced')->first();

        $this->assertSame(LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW, $log->metadata['old_status']);
        $this->assertSame(LandTransferApplication::STATUS_ENDORSED_LTI, $log->metadata['new_status']);
        $this->assertSame('Status advancement only. No ownership transfer or registry mutation performed.', $log->metadata['scope_note']);
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
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
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
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
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

    public function test_release_creates_application_and_clearance_audit_logs(): void
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

        $parcel = Parcel::create([
            'parcel_code' => 'AUDIT-RELEASE-PARCEL-001',
            'title_no' => 'T-AUDIT-RELEASE-001',
            'lot_number' => 'LOT-AUDIT-RELEASE-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.0000,
            'status' => 'active',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'AUDIT-RELEASE-001',
            'transferor_name' => 'Audit Transferor',
            'transferee_name' => 'Audit Transferee',
            'transferor_landowner_id' => $transferor->id,
            'transferee_landowner_id' => $transferee->id,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_FOR_RELEASING,
            'encoded_by' => $staffUser->id,
        ]);

        $application->forceFill([
            'ltc_form4_subject_land_findings' => ['ra6657_not_covered_not_tenanted_retained_area'],
            'ltc_form4_recommendation_findings' => ['application_complete'],
            'ltc_form4_recommendation_decision' => 'approval',
            'ltc_form4_certified_at' => now()->toDateString(),
            'ltc_form4_certifying_officer_name' => 'Authorized Review Officer',
        ])->save();

        ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'title_no' => $parcel->title_no,
            'lot_number' => $parcel->lot_number,
            'area_hectares' => 1.0000,
        ]);

        $this->actingAs($staffUser)->post(
            route('staff.applications.approve', $application),
            [
                'final_decision_confirmation' => '1',
                'decision_reason' => 'Audit release reason',
                'decision_notes' => 'Audit release notes',
            ]
        )->assertSessionHas('success');

        $application->refresh();

        $this->assertSame(LandTransferApplication::STATUS_RELEASED, $application->status);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staffUser->id,
            'land_transfer_application_id' => $application->id,
            'auditable_type' => LandTransferApplication::class,
            'auditable_id' => $application->id,
            'action' => 'application_released',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staffUser->id,
            'land_transfer_application_id' => $application->id,
            'action' => 'clearance_generated',
        ]);

        $releaseLog = AuditLog::where('action', 'application_released')->first();

        $this->assertSame('Audit release reason', $releaseLog->metadata['decision_reason']);
        $this->assertSame('Audit release notes', $releaseLog->metadata['decision_notes']);
        $this->assertTrue($releaseLog->metadata['form4_recommendation_matches_final_decision']);
        $this->assertFalse($releaseLog->metadata['registry_mutation_performed']);

        $clearanceLog = AuditLog::where('action', 'clearance_generated')->first();

        $this->assertSame(LandTransferApplication::STATUS_RELEASED, $clearanceLog->metadata['decision_status']);
        $this->assertSame(1, $clearanceLog->metadata['parcel_count']);
    }
}
