<?php

namespace Tests\Feature;

use App\Models\LandTransferApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffDashboardDecisionMetricTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_decisions_today_uses_decision_time_not_later_release_updates(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        LandTransferApplication::create([
            'application_code' => 'DASHBOARD-DECISION-TODAY',
            'transferor_name' => 'Today Transferor',
            'transferee_name' => 'Today Transferee',
            'status' => LandTransferApplication::STATUS_APPROVED,
            'decision_recorded_at' => now(),
            'reviewed_at' => now(),
            'encoded_by' => $staff->id,
        ]);

        $olderDecision = LandTransferApplication::create([
            'application_code' => 'DASHBOARD-DECISION-OLDER',
            'transferor_name' => 'Older Transferor',
            'transferee_name' => 'Older Transferee',
            'status' => LandTransferApplication::STATUS_APPROVED,
            'decision_recorded_at' => now()->subDay(),
            'reviewed_at' => now()->subDay(),
            'encoded_by' => $staff->id,
        ]);

        // Simulate a later administrative release-state update occurring today.
        DB::table('land_transfer_applications')
            ->where('id', $olderDecision->id)
            ->update([
                'release_status' => LandTransferApplication::RELEASE_READY,
                'ready_for_release_at' => now(),
            ]);
        $olderDecision->refresh();

        $response = $this->actingAs($staff)->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertViewHas('todaySummary', function (array $summary): bool {
            $decisionRow = collect($summary)->firstWhere('label', 'Final Decisions Today');

            return (int) ($decisionRow['value'] ?? -1) === 1;
        });
    }

    public function test_final_decisions_today_uses_reviewed_at_only_as_legacy_fallback(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $legacyApplication = LandTransferApplication::create([
            'application_code' => 'DASHBOARD-LEGACY-DECISION-TODAY',
            'transferor_name' => 'Legacy Transferor',
            'transferee_name' => 'Legacy Transferee',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'decision_recorded_at' => null,
            'reviewed_at' => now(),
            'encoded_by' => $staff->id,
        ]);

        // Historical fixtures bypass the current-workflow observer because
        // negative final statuses can no longer be created by normal model writes.
        DB::table('land_transfer_applications')
            ->where('id', $legacyApplication->id)
            ->update(['status' => LandTransferApplication::STATUS_DENIED]);

        $response = $this->actingAs($staff)->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertViewHas('todaySummary', function (array $summary): bool {
            $decisionRow = collect($summary)->firstWhere('label', 'Final Decisions Today');

            return (int) ($decisionRow['value'] ?? -1) === 1;
        });
    }
}
