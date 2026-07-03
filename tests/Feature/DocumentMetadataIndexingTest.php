<?php

namespace Tests\Feature;

use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentMetadataIndexingTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_upload_document_with_metadata_indexing_fields(): void
    {
        Storage::fake('local');

        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'DOC-META-001',
            'transferor_name' => 'Metadata Transferor',
            'transferee_name' => 'Metadata Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        $requiredDocument = RequiredDocument::forceCreate([
            'name' => 'Electronic Copy of Title',
            'applies_to' => 'transferor',
            'is_mandatory' => true,
            'legal_basis' => 'Metadata indexing test basis',
        ]);

        $this->actingAs($staffUser)->post(
            route('staff.applications.documents.store', [
                'application' => $application,
                'requiredDocument' => $requiredDocument,
            ]),
            [
                'file' => UploadedFile::fake()->create('sample-title.pdf', 100, 'application/pdf'),
                'annex_reference' => 'Annex A',
                'remarks' => 'Uploaded with metadata indexing.',
                'document_reference_number' => 'TCT-12345',
                'document_metadata' => [
                    'issuing_office' => 'Registry of Deeds',
                    'date_issued' => '2026-05-04',
                    'reference_lot_or_parcel' => 'Lot 123',
                    'transfer_document_title' => 'Deed of Sale',
                    'transferor_names' => 'Metadata Transferor',
                    'transferee_names' => 'Metadata Transferee',
                    'transfer_area' => '10,000 sq.m.',
                    'transfer_lot_number' => 'Lot 123-A',
                    'notarization_date' => '2026-05-05',
                    'notary_public' => 'Atty. Test Notary',
                    'notarial_page_number' => '12',
                    'notarial_book_number' => '3',
                    'notarial_document_number' => '456',
                    'notarial_series' => '2026',
                    'verification_notes' => 'Encoded for indexing only.',
                ],
            ]
        )->assertSessionHas('success');

        $document = ApplicationDocument::where('land_transfer_application_id', $application->id)
            ->where('required_document_id', $requiredDocument->id)
            ->first();

        $this->assertNotNull($document);

        $this->assertSame('TCT-12345', $document->document_reference_number);
        $this->assertSame('Registry of Deeds', $document->document_metadata['issuing_office']);
        $this->assertSame('2026-05-04', $document->document_metadata['date_issued']);
        $this->assertSame('Lot 123', $document->document_metadata['reference_lot_or_parcel']);
        $this->assertSame('Deed of Sale', $document->document_metadata['transfer_document_title']);
        $this->assertSame('Metadata Transferor', $document->document_metadata['transferor_names']);
        $this->assertSame('Metadata Transferee', $document->document_metadata['transferee_names']);
        $this->assertSame('10,000 sq.m.', $document->document_metadata['transfer_area']);
        $this->assertSame('Lot 123-A', $document->document_metadata['transfer_lot_number']);
        $this->assertSame('2026-05-05', $document->document_metadata['notarization_date']);
        $this->assertSame('Atty. Test Notary', $document->document_metadata['notary_public']);
        $this->assertSame('12', $document->document_metadata['notarial_page_number']);
        $this->assertSame('3', $document->document_metadata['notarial_book_number']);
        $this->assertSame('456', $document->document_metadata['notarial_document_number']);
        $this->assertSame('2026', $document->document_metadata['notarial_series']);
        $this->assertSame('Encoded for indexing only.', $document->document_metadata['verification_notes']);
        $this->assertSame($staffUser->id, $document->metadata_encoded_by);
        $this->assertNotNull($document->metadata_encoded_at);

        $log = AuditLog::where('action', 'document_uploaded')->first();

        $this->assertNotNull($log);
        $this->assertSame('TCT-12345', $log->metadata['document_reference_number']);
        $this->assertSame('Registry of Deeds', $log->metadata['document_metadata']['issuing_office']);
        $this->assertSame('Lot 123', $log->metadata['document_metadata']['reference_lot_or_parcel']);
        $this->assertSame('Deed of Sale', $log->metadata['document_metadata']['transfer_document_title']);
        $this->assertSame('Atty. Test Notary', $log->metadata['document_metadata']['notary_public']);
        $this->assertSame('456', $log->metadata['document_metadata']['notarial_document_number']);
    }

    public function test_document_metadata_is_locked_after_final_decision(): void
    {
        Storage::fake('local');

        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'DOC-META-LOCK-001',
            'transferor_name' => 'Locked Transferor',
            'transferee_name' => 'Locked Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_APPROVED,
            'encoded_by' => $staffUser->id,
        ]);

        $requiredDocument = RequiredDocument::forceCreate([
            'name' => 'Recent Tax Declaration',
            'applies_to' => 'transferor',
            'is_mandatory' => true,
            'legal_basis' => 'Metadata lock test basis',
        ]);

        $this->actingAs($staffUser)->post(
            route('staff.applications.documents.store', [
                'application' => $application,
                'requiredDocument' => $requiredDocument,
            ]),
            [
                'file' => UploadedFile::fake()->create('tax-declaration.pdf', 100, 'application/pdf'),
                'document_reference_number' => 'TD-99999',
                'document_metadata' => [
                    'issuing_office' => 'Assessor Office',
                    'date_issued' => '2026-05-04',
                    'reference_lot_or_parcel' => 'Lot 999',
                    'verification_notes' => 'This should not be saved.',
                ],
            ]
        )->assertSessionHas('error');

        $this->assertDatabaseMissing('application_documents', [
            'land_transfer_application_id' => $application->id,
            'required_document_id' => $requiredDocument->id,
            'document_reference_number' => 'TD-99999',
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'land_transfer_application_id' => $application->id,
            'action' => 'document_uploaded',
        ]);
    }
}