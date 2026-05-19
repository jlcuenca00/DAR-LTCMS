<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Services\ApplicationClearanceService;
use App\Services\AuditLogger;
use App\Services\LandholdingAreaValidationService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApplicationWorkflowController extends Controller
{
    /**
     * Submit: draft -> pending_review
     * Allowed even if validations fail (assistive system).
     */
    public function submit(LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return back()->withErrors(['status' => 'This application is already finalized and cannot be submitted again.']);
        }

        if ($application->status !== 'draft') {
            return back()->withErrors(['status' => 'Only draft applications can be submitted.']);
        }

                $oldStatus = $application->status;

        $application->status = LandTransferApplication::STATUS_PENDING_REVIEW;
        $application->save();

        AuditLogger::record(
            'application_submitted',
            $application,
            $application,
            [
                'old_status' => $oldStatus,
                'new_status' => $application->status,
            ]
        );

        app(NotificationService::class)->notifyStaffApplicationSubmitted($application);
        app(NotificationService::class)->notifyLinkedLandownersStatusChanged($application, 'submitted for review');

        return back()->with('success', 'Application submitted for review.');
    }

    /**
 * Approve: pending_review -> approved
 *
 * Scope rule:
 * Approval generates and records a clearance decision only.
 * It does NOT automatically transfer land ownership or mutate registry records.
 */
public function approve(Request $request, LandTransferApplication $application)
{
    if ($application->isFinalized()) {
        return back()->withErrors(['status' => 'This application already has a final decision and cannot be approved again.']);
    }
    if ($application->status !== 'pending_review') {
        return back()->withErrors(['status' => 'Only applications pending review can be approved.']);
    }

    if (!$application->transferor_landowner_id || !$application->transferee_landowner_id) {
        return back()->with('error', 'Cannot approve: Transferor and Transferee must be linked to Landowner records.');
    }

    [$snapshot, $hasCriticalFailures] = $this->buildValidationSnapshot($application);

    if ($hasCriticalFailures) {
        return back()->withErrors([
            'validation' => 'Critical validation failures detected. Mark as Not Approved or resolve issues.'
        ]);
    }

    try {
        DB::transaction(function () use ($request, $application, $snapshot) {
            $application = LandTransferApplication::findOrFail($application->id);

            $application->status = 'approved';
            $application->reviewed_by = Auth::id();
            $application->reviewed_at = now();
            $application->validated_at = now();
            $application->validation_snapshot = $snapshot;
            $application->decision_reason = $request->input('decision_reason');
            $application->decision_notes = $request->input('decision_notes');

            /*
             * Important:
             * Do not call LandRegistryMutationService here.
             * This system is limited to clearance generation, processing,
             * monitoring, and record keeping only.
             */
            $application->registry_mutated_at = null;
            $application->registry_mutated_by = null;

                        $application->save();

            AuditLogger::record(
                'application_approved',
                $application,
                $application,
                [
                    'decision_reason' => $application->decision_reason,
                    'decision_notes' => $application->decision_notes,
                    'validated_at' => optional($application->validated_at)->toDateTimeString(),
                    'has_validation_snapshot' => ! empty($application->validation_snapshot),
                    'registry_mutation_performed' => false,
                ]
            );

            app(ApplicationClearanceService::class)->generateForDecision($application, Auth::id());

            app(NotificationService::class)->notifyStaffApplicationApproved($application);
            app(NotificationService::class)->notifyLinkedLandownersFinalDecision($application);
        });
    } catch (\Throwable $e) {
        return back()->with('error', 'Approval failed: ' . $e->getMessage());
    }

    return back()->with('success', 'Application approved and clearance generated.');
}

    /**
     * Not Approved: pending_review -> not_approved
     * Allowed even if validations fail (expected).
     */
    public function notApproved(Request $request, LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return back()->withErrors(['status' => 'This application already has a final decision and cannot be marked Not Approved again.']);
        }
        if (!in_array($application->status, ['pending_review', 'draft'], true)) {
            return back()->withErrors(['status' => 'Only draft or pending review applications can be marked Not Approved.']);
        }

        [$snapshot, $hasCriticalFailures] = $this->buildValidationSnapshot($application);

        try {
            DB::transaction(function () use ($request, $application, $snapshot) {
                $application = LandTransferApplication::findOrFail($application->id);
                $application->status = 'not_approved';
                $application->reviewed_by = Auth::id();
                $application->reviewed_at = now();
                $application->validated_at = now();
                $application->validation_snapshot = $snapshot;
                $application->decision_reason = $request->input('decision_reason');
                $application->decision_notes = $request->input('decision_notes');
                                $application->save();

                AuditLogger::record(
                    'application_not_approved',
                    $application,
                    $application,
                    [
                        'decision_reason' => $application->decision_reason,
                        'decision_notes' => $application->decision_notes,
                        'validated_at' => optional($application->validated_at)->toDateTimeString(),
                        'has_validation_snapshot' => ! empty($application->validation_snapshot),
                        'registry_mutation_performed' => false,
                    ]
                );

                app(ApplicationClearanceService::class)->generateForDecision($application, Auth::id());

                app(NotificationService::class)->notifyStaffApplicationNotApproved($application);
                app(NotificationService::class)->notifyLinkedLandownersFinalDecision($application);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Not Approved decision failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Application marked as Not Approved and clearance generated.');
    }

    /**
     * Build an audit-ready snapshot of validations at decision time.
     * Returns: [snapshotArray, hasCriticalFailuresBool]
     */
    private function buildValidationSnapshot(LandTransferApplication $application): array
    {
        $hectareValidation = app(LandholdingAreaValidationService::class)
            ->forApplication($application);

        $mandatoryIds = RequiredDocument::where('is_mandatory', true)->pluck('id')->all();
        $uploadedIds = $application->documents()->pluck('required_document_id')->all();

        $missingMandatory = array_values(array_diff($mandatoryIds, $uploadedIds));
        $missingMandatoryCount = count($missingMandatory);

        $hasCriticalFailures = $hectareValidation['exceeds_limit'] || $missingMandatoryCount > 0;

        $snapshot = [
            'computed_at' => now()->toDateTimeString(),
            'five_hectare' => [
                'current_approved_total' => (float) $hectareValidation['current_active_total'],
                'pending_incoming_total' => (float) $hectareValidation['pending_incoming_total'],
                'this_application_total' => (float) $hectareValidation['this_application_total'],
                'projected_total' => (float) $hectareValidation['projected_total'],
                'remaining_after_projection' => (float) $hectareValidation['remaining_after_projection'],
                'exceeds_limit' => (bool) $hectareValidation['exceeds_limit'],
                'limit' => (float) $hectareValidation['limit'],
                'scope_note' => $hectareValidation['scope_note'],
            ],
            'documents' => [
                'missing_mandatory_count' => $missingMandatoryCount,
                'missing_mandatory_ids' => $missingMandatory,
            ],
        ];

        return [$snapshot, $hasCriticalFailures];
    }
}
