<?php

namespace App\Services;

use App\Models\LandTransferApplication;
use Illuminate\Validation\ValidationException;

class ApplicationWorkflowTransitionService
{
    public const CONTEXT_ADVANCE = 'advance';
    public const CONTEXT_COMPLIANCE_REQUEST = 'compliance_request';
    public const CONTEXT_COMPLIANCE_RESUME = 'compliance_resume';
    public const CONTEXT_FINAL_APPROVAL = 'final_approval';

    public function transition(
        LandTransferApplication $application,
        string $toStatus,
        string $context
    ): void {
        $fromStatus = (string) $application->getRawOriginal('status');

        $this->assertAllowed($fromStatus, $toStatus, $context);

        $application->runAuthorizedWorkflowStatusMutation(function () use ($application, $toStatus) {
            $application->status = $toStatus;
            $application->save();
        });
    }

    private function assertAllowed(string $fromStatus, string $toStatus, string $context): void
    {
        $normalizedFromStatus = match ($fromStatus) {
            LandTransferApplication::STATUS_DRAFT,
            LandTransferApplication::STATUS_PENDING_REVIEW => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            default => $fromStatus,
        };

        $allowed = match ($context) {
            self::CONTEXT_ADVANCE =>
                (LandTransferApplication::workflowTransitions()[$normalizedFromStatus] ?? null) === $toStatus,

            self::CONTEXT_COMPLIANCE_REQUEST =>
                $toStatus === LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE
                && in_array($normalizedFromStatus, LandTransferApplication::COMPLIANCE_RESUME_STATUSES, true),

            self::CONTEXT_COMPLIANCE_RESUME =>
                $fromStatus === LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE
                && in_array($toStatus, LandTransferApplication::COMPLIANCE_RESUME_STATUSES, true),

            self::CONTEXT_FINAL_APPROVAL =>
                $fromStatus === LandTransferApplication::STATUS_FOR_RELEASING
                && $toStatus === LandTransferApplication::STATUS_APPROVED,

            default => false,
        };

        if (! $allowed) {
            throw ValidationException::withMessages([
                'status' => 'The requested workflow status transition is not allowed from the application’s current state.',
            ]);
        }
    }
}
