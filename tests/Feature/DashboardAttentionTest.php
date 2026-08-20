<?php

namespace Tests\Feature;

use App\Http\Controllers\Staff\StaffDashboardController;
use App\Models\ApplicationDocument;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\View\View;
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

        $this->actingAs($staff);

        $missing = $this->dashboardData('missing_requirements');
        $this->assertSame('Incomplete Requirements', $missing['attentionFocusLabel']);
        $this->assertEqualsCanonicalizing(
            ['APP-ATTN-INCOMPLETE', 'APP-ATTN-STALE'],
            $missing['actionApplications']->pluck('application_code')->all()
        );

        $completeData = $this->dashboardData('requirements_complete');
        $this->assertSame('Requirements Complete', $completeData['attentionFocusLabel']);
        $this->assertSame(
            ['APP-ATTN-COMPLETE'],
            $completeData['actionApplications']->pluck('application_code')->all()
        );

        $staleData = $this->dashboardData('stale');
        $this->assertSame('No Update for More Than 7 Days', $staleData['attentionFocusLabel']);
        $this->assertSame(
            ['APP-ATTN-STALE'],
            $staleData['actionApplications']->pluck('application_code')->all()
        );
    }

    private function dashboardData(string $attention): array
    {
        $request = Request::create('/staff/dashboard', 'GET', ['attention' => $attention]);
        $view = app(StaffDashboardController::class)($request);

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('dashboards.staff', $view->name());

        return $view->getData();
    }
}
