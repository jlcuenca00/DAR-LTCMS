<?php

namespace Tests\Feature;

use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_executable_supporting_files_are_rejected(): void
    {
        Storage::fake('local');

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $application = LandTransferApplication::create([
            'application_code' => 'UPLOAD-SEC-001',
            'transferor_name' => 'Transferor',
            'transferee_name' => 'Transferee',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);
        $requiredDocument = RequiredDocument::forceCreate([
            'name' => 'Security Test Requirement',
            'applies_to' => 'transferor',
            'is_mandatory' => true,
        ]);

        $this->actingAs($staff)
            ->post(route('staff.applications.documents.store', [$application, $requiredDocument]), [
                'file' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('application_documents', 0);
    }
}
