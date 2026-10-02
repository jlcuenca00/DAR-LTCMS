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
use Illuminate\Support\Facades\DB;
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

        $parcel = Parcel::create([
            'parcel_code' => 'AUDIT-ADVANCE-PARCEL-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.0000,
            'status' => 'active',
        ]);

        ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'area_hectares' => 1.0000,
        ]);

        $this->actingAs($staffUser)
            ->post(route('staff.applications.submit', $application), [
                'expected_status' => $application->fresh()->status,
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'payment_order_reference' => 'OP-AUDIT-ADVANCE-001',
            ])
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
        $this->assertSame(LandTransferApplication::STATUS_AWAITING_PAYMENT, $log->metadata['new_status']);
        $this->assertSame('Legal Division', $log->metadata['administrative_authority']);
        $this->assertSame($staffUser->id, $log->metadata['recorded_by_user_id']);
        $this->assertSame('Legal Clearance Staff', $log->metadata['recorded_by_role']);
        $this->assertStringContainsString('Administrative workflow recording only.', $log->metadata['scope_note']);
        $this->assertTrue($log->metadata['requirements_checked']);
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
        Storage::assertMissing('application-documents/audit-existing.pdf');

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

    public function test_final_approval_creates_application_and_clearance_audit_logs_without_mutating_ownership(): void
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
            'parcel_code' => 'AUDIT-APPROVAL-PARCEL-001',
            'title_no' => 'T-AUDIT-APPROVAL-001',
            'lot_number' => 'LOT-AUDIT-APPROVAL-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.0000,
            'status' => 'active',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'AUDIT-APPROVAL-001',
            'transferor_name' => 'Audit Transferor',
            'transferee_name' => 'Audit Transferee',
            'transferor_landowner_id' => $transferor->id,
            'transferee_landowner_id' => $transferee->id,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_FOR_RELEASING,
            'encoded_by' => $staffUser->id,
        ]);

        DB::table('land_transfer_applications')
            ->where('id', $application->id)
            ->update([
                'ltc_form4_subject_land_findings' => json_encode(['ra6657_not_covered_not_tenanted_retained_area']),
                'ltc_form4_recommendation_findings' => json_encode(['application_complete']),
                'ltc_form4_recommendation_decision' => 'approval',
                'ltc_form4_certified_at' => now()->toDateString(),
                'ltc_form4_certifying_officer_name' => 'Authorized Review Officer',
                'payment_order_reference' => 'OP-AUDIT-APPROVAL-001',
                'payment_order_issued_at' => now(),
                'or_number' => 'OR-AUDIT-APPROVAL-001',
                'or_date' => now()->toDateString(),
                'amount_paid' => config('dar_ltc.filing_fee', 2000),
                'csw_reference' => 'CSW-AUDIT-APPROVAL-001',
                'csw_completed_at' => now(),
                'csw_prepared_by' => $staffUser->id,
            ]);
        $application->refresh();

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
                'decision_officer_name' => 'PARPO II Test Signatory',
                'decision_date' => now()->toDateString(),
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

        $application->refresh();
        $this->assertSame(LandTransferApplication::FINAL_DECISION_AUTHORITY, $application->decision_authority);
        $this->assertSame('PARPO II Test Signatory', $application->decision_officer_name);
        $this->assertSame(now()->toDateString(), $application->decision_date?->toDateString());
        $this->assertSame($staffUser->id, $application->decision_recorded_by);
        $this->assertNotNull($application->decision_recorded_at);

        $this->assertSame(LandTransferApplication::FINAL_DECISION_AUTHORITY, $approvalLog->metadata['decision_authority']);
        $this->assertSame('PARPO II Test Signatory', $approvalLog->metadata['decision_officer_name']);
        $this->assertSame($staffUser->id, $approvalLog->metadata['recorded_by_user_id']);
        $this->assertSame('Legal Clearance Staff', $approvalLog->metadata['recorded_by_role']);
        $this->assertSame('Audit approval reason', $approvalLog->metadata['decision_reason']);
        $this->assertSame('Audit approval notes', $approvalLog->metadata['decision_notes']);
        $this->assertTrue($approvalLog->metadata['form4_recommendation_matches_final_decision']);
        $this->assertFalse($approvalLog->metadata['ownership_transfer_performed']);
        $this->assertFalse($approvalLog->metadata['registry_mutation_performed']);

        $clearanceLog = AuditLog::where('action', 'clearance_generated')->first();

        $this->assertSame(LandTransferApplication::STATUS_APPROVED, $clearanceLog->metadata['decision_status']);
        $this->assertSame(1, $clearanceLog->metadata['parcel_count']);
    }
}
