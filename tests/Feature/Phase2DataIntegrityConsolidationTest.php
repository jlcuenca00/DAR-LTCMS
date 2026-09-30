<?php

namespace Tests\Feature;

use App\Models\LandTransferApplication;
use Tests\TestCase;

class Phase2DataIntegrityConsolidationTest extends TestCase
{
    public function test_current_final_status_contract_is_approved_and_not_approved(): void
    {
        $this->assertSame([
            LandTransferApplication::STATUS_APPROVED,
            LandTransferApplication::STATUS_NOT_APPROVED,
        ], LandTransferApplication::FINAL_STATUSES);

        $this->assertSame('approved', LandTransferApplication::STATUS_APPROVED);
        $this->assertSame('not_approved', LandTransferApplication::STATUS_NOT_APPROVED);
        $this->assertSame('Not Approved', LandTransferApplication::statusLabels()[LandTransferApplication::STATUS_NOT_APPROVED]);

        $workflowOptions = LandTransferApplication::workflowStatusOptions();

        $this->assertArrayHasKey(LandTransferApplication::STATUS_NOT_APPROVED, $workflowOptions);
        $this->assertArrayNotHasKey(LandTransferApplication::STATUS_DENIED, $workflowOptions);
    }

    public function test_legacy_denied_and_released_values_remain_readable_and_finalized(): void
    {
        $this->assertSame([
            LandTransferApplication::STATUS_RELEASED,
            LandTransferApplication::STATUS_DENIED,
        ], LandTransferApplication::LEGACY_FINAL_STATUSES);

        foreach ([
            LandTransferApplication::STATUS_RELEASED,
            LandTransferApplication::STATUS_DENIED,
        ] as $status) {
            $application = new LandTransferApplication(['status' => $status]);

            $this->assertTrue($application->isFinalized());
            $this->assertFalse($application->isEditable());
        }

        $this->assertSame(
            'Not Approved (Legacy Record)',
            LandTransferApplication::statusLabels()[LandTransferApplication::STATUS_DENIED]
        );
    }
}
