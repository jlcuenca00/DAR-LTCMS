<?php

namespace App\Services;

use App\Models\LandTransferApplication;
use Illuminate\Validation\ValidationException;

class ApplicationWorkflowEvidenceService
{
    public const CONTEXT_INTAKE = 'intake';
    public const CONTEXT_PAYMENT = 'payment';
    public const CONTEXT_CSW = 'csw';
    public const CONTEXT_FINAL_DECISION = 'final_decision';

    private const CONTEXT_FIELDS = [
        self::CONTEXT_INTAKE => [
            'applicant_is_juridical_entity',
            'payment_order_reference',
            'payment_order_issued_at',
        ],
        self::CONTEXT_PAYMENT => [
            'or_number',
            'or_date',
            'amount_paid',
        ],
        self::CONTEXT_CSW => [
            'csw_reference',
            'csw_completed_at',
            'csw_prepared_by',
            'csw_notes',
        ],
        self::CONTEXT_FINAL_DECISION => [
            'reviewed_by',
            'reviewed_at',
            'decision_reason',
            'decision_notes',
            'decision_authority',
            'decision_officer_name',
            'decision_date',
            'decision_recorded_by',
            'decision_recorded_at',
            'validated_at',
            'validation_snapshot',
        ],
    ];

    public static function protectedFields(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::CONTEXT_FIELDS))));
    }

    public function apply(LandTransferApplication $application, string $context, array $attributes): void
    {
        $allowedFields = self::CONTEXT_FIELDS[$context] ?? null;

        if ($allowedFields === null) {
            throw ValidationException::withMessages([
                'workflow' => 'Unknown workflow evidence context.',
            ]);
        }

        $invalidFields = array_values(array_diff(array_keys($attributes), $allowedFields));

        if ($invalidFields !== []) {
            throw ValidationException::withMessages([
                'workflow' => 'The workflow evidence action attempted to write fields outside its authorized context.',
            ]);
        }

        $status = (string) $application->getRawOriginal('status');
        $statusAllowed = match ($context) {
            self::CONTEXT_INTAKE => in_array($status, [
                LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
                LandTransferApplication::STATUS_DRAFT,
                LandTransferApplication::STATUS_PENDING_REVIEW,
            ], true),
            self::CONTEXT_PAYMENT => $status === LandTransferApplication::STATUS_AWAITING_PAYMENT,
            self::CONTEXT_CSW => $status === LandTransferApplication::STATUS_LEGAL_EVALUATION,
            self::CONTEXT_FINAL_DECISION => $status === LandTransferApplication::STATUS_FOR_RELEASING,
            default => false,
        };

        if (! $statusAllowed) {
            throw ValidationException::withMessages([
                'workflow' => 'Workflow evidence cannot be recorded from the application’s current stage.',
            ]);
        }

        $application->runAuthorizedWorkflowEvidenceMutation(function () use ($application, $attributes) {
            $application->forceFill($attributes);
            $application->save();
        });
    }
}
