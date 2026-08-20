<?php

namespace Tests\Feature;

use App\Models\ApplicationDocument;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAttentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_attention_filters_separate_incomplete_complete_and_stale_active_applications(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $required = RequiredDocument::create([
            'name' => 'Dashboard Required Document',
            'applies_to' => 'transferor',
            'is_mandatory' => true,
            'blocks_acceptance' => true,
            'requirement_classification' => RequiredDocument::CLASSIFICATION_MANDATORY,
        ]);

        $incomplete = LandTransferApplication::create([
            'application_code' => 'APP-ATTN-INCOMPLETE',
            'transferor_name' => 'Incomplete Transferor',
            'transferee_name' => 'Incomplete Transferee',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        $complete = LandTransferApplication::create([
            'application_code' => 'APP-ATTN-COMPLETE',
            'transferor_name' => 'Complete Transferor',
            'transferee_name' => 'Complete Transferee',
            'status' => LandTransferApplication::STATUS_ENDORSED_LTI,
            'encoded_by' => $staff->id,
        ]);

        ApplicationDocument::create([
            'land_transfer_application_id' => $complete->id,
            'required_document_id' => $required->id,
            'original_filename' => 'metadata-only',
            'uploaded_by' => $staff->id,
            'document_metadata' => ['document_number' => 'TEST-001'],
        ]);

        $stale = LandTransferApplication::create([
            'application_code' => 'APP-ATTN-STALE',
            'transferor_name' => 'Stale Transferor',
            'transferee_name' => 'Stale Transferee',
            'status' => LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL,
            'encoded_by' => $staff->id,
        ]);
        $stale->timestamps = false;
        $stale->updated_at = now()->subDays(9);
        $stale->save();

        $this->actingAs($staff)
            ->get(route('staff.dashboard', ['attention' => 'missing_requirements']))
            ->assertOk()
            ->assertSee('Incomplete Requirements')
            ->assertSee('APP-ATTN-INCOMPLETE')
            ->assertSee('APP-ATTN-STALE')
            ->assertDontSee('APP-ATTN-COMPLETE');

        $this->actingAs($staff)
            ->get(route('staff.dashboard', ['attention' => 'requirements_complete']))
            ->assertOk()
            ->assertSee('Requirements Complete')
            ->assertSee('APP-ATTN-COMPLETE')
            ->assertDontSee('APP-ATTN-INCOMPLETE');

        $this->actingAs($staff)
            ->get(route('staff.dashboard', ['attention' => 'stale']))
            ->assertOk()
            ->assertSee('No Update for More Than 7 Days')
            ->assertSee('APP-ATTN-STALE')
            ->assertDontSee('APP-ATTN-COMPLETE');
    }
}
