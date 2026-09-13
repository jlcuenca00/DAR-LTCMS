<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\LandTransferApplication;
use App\Services\ApplicationClearanceService;
use App\Services\ApplicationRequirementService;
use App\Services\AuditLogger;
use App\Services\LandholdingAreaValidationService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationWorkflowController extends Controller
{
    /**
     * Advance one administrative stage. Each gate mirrors the office sequence
     * in the DAR A.O. No. 4, s. 2021 Citizen's Charter. No stage advancement
     * transfers ownership or mutates Registry of Deeds records.
     */
    public function submit(Request $request, LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return back()->withErrors(['status' => 'This application already has a final PARPO II decision and cannot be advanced.']);
        }

        $oldStatus = $application->status;
        $currentStatus = match ($oldStatus) {
            LandTransferApplication::STATUS_DRAFT,
            LandTransferApplication::STATUS_PENDING_REVIEW => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            default => $oldStatus,
        };

        if ($currentStatus !== $oldStatus) {
            $application->status = $currentStatus;
            $application->save();
        }

        $nextStatus = $application->nextWorkflowStatus();

        if (! $nextStatus) {
            return back()->withErrors(['status' => 'No normal stage advancement is available from the current workflow status.']);
        }

        $auditMetadata = [
            'old_status' => $oldStatus,
            'new_status' => $nextStatus,
            'scope_note' => 'Administrative status advancement only. No ownership transfer or registry mutation was performed.',
        ];

        // Legal intake / completeness review -> payment-order stage.
        if ($currentStatus === LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW) {
            if ($request->has('applicant_is_juridical_entity')) {
                $application->applicant_is_juridical_entity = $request->boolean('applicant_is_juridical_entity');
                $application->save();
            }

            $requirements = app(ApplicationRequirementService::class)->evaluate($application->fresh());

            if (! $requirements['complete']) {
                return back()->withErrors(array_merge([
                    'validation' => 'Complete the applicable documentary requirements before issuing the payment order.',
                ], $requirements['errors']));
            }

            $validated = $request->validate([
                'payment_order_reference' => ['nullable', 'string', 'max:150'],
            ]);

            $application->payment_order_reference = $validated['payment_order_reference']
                ?? ('OP-' . $application->application_code);
            $application->payment_order_issued_at = now();
            $application->save();

            $auditMetadata['requirements_checked'] = true;
            $auditMetadata['payment_order_reference'] = $application->payment_order_reference;
            $auditMetadata['requirements'] = [
                'blocking_count' => $requirements['blocking_count'],
                'complete_blocking_count' => $requirements['complete_blocking_count'],
            ];
        }

        // Payment is performed by the DAR cashier outside DAR-LTCMS. The system
        // only records the resulting Official Receipt before LTID endorsement.
        if ($currentStatus === LandTransferApplication::STATUS_AWAITING_PAYMENT) {
            $validated = $request->validate([
                'or_number' => ['required', 'string', 'max:100'],
                'or_date' => ['required', 'date'],
                'amount_paid' => ['required', 'numeric', 'min:0'],
            ]);

            $expectedFee = (float) config('dar_ltc.filing_fee', 2000);
            $amountPaid = round((float) $validated['amount_paid'], 2);

            if (abs($amountPaid - $expectedFee) > 0.009) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'The recorded filing fee must be ₱' . number_format($expectedFee, 2) . ' before endorsement to LTID.',
                ]);
            }

            $application->or_number = $validated['or_number'];
            $application->or_date = $validated['or_date'];
            $application->amount_paid = $amountPaid;
            $application->save();

            $auditMetadata['payment_recorded'] = true;
            $auditMetadata['or_number'] = $application->or_number;
            $auditMetadata['or_date'] = optional($application->or_date)->toDateString();
            $auditMetadata['amount_paid'] = $amountPaid;
        }

        // LTID returns Form No. 4 / verification records to Legal. Form No. 4
        // must be completed before Legal begins its formal evaluation/CSW stage.
        if ($currentStatus === LandTransferApplication::STATUS_RETURNED_TO_LEGAL) {
            $workflow = $this->workflowReadinessSnapshot($application);

            if (! $workflow['form4_complete']) {
                $missing = implode(', ', $workflow['form4_missing_items']);
                return back()->withErrors([
                    'form4' => 'Complete LTC Form No. 4 before beginning Legal evaluation.'
                        . ($missing !== '' ? ' Missing: ' . $missing . '.' : ''),
                ]);
            }
        }

        // Legal evaluation -> Chief Legal. Record completion of Completed Staff
        // Work without pretending that CSW itself is the final legal decision.
        if ($currentStatus === LandTransferApplication::STATUS_LEGAL_EVALUATION) {
            $validated = $request->validate([
                'csw_reference' => ['nullable', 'string', 'max:150'],
                'csw_notes' => ['nullable', 'string', 'max:4000'],
            ]);

            $application->csw_reference = $validated['csw_reference']
                ?? ('CSW-' . $application->application_code);
            $application->csw_completed_at = now();
            $application->csw_prepared_by = Auth::id();
            $application->csw_notes = $validated['csw_notes'] ?? null;
            $application->save();

            $auditMetadata['csw_completed'] = true;
            $auditMetadata['csw_reference'] = $application->csw_reference;
        }

        if ($currentStatus === LandTransferApplication::STATUS_ENDORSED_CHIEF_LEGAL
            && (! $application->csw_completed_at || blank($application->csw_reference))) {
            return back()->withErrors([
                'csw' => 'Completed Staff Work must be recorded before forwarding the application to PARPO II.',
            ]);
        }

        // Before PARPO II receives the final-decision action, verify the full
        // administrative record. Form 4 remains recommendatory only.
        if ($nextStatus === LandTransferApplication::STATUS_FOR_RELEASING) {
            [$snapshot, $readinessErrors] = $this->decisionReadiness($application);

            if (! empty($readinessErrors)) {
                return back()->withErrors(array_merge([
                    'validation' => 'Resolve the following before placing the application for PARPO II decision:',
                ], $readinessErrors));
            }

            $auditMetadata['decision_readiness_checked'] = true;
            $auditMetadata['decision_readiness'] = $snapshot['workflow_readiness'];
        }

        $application->status = $nextStatus;
        $application->save();

        AuditLogger::record(
            'application_status_advanced',
            $application,
            $application,
            $auditMetadata,
            Auth::id()
        );

        $statusLabel = $application->statusLabel();

        app(NotificationService::class)->notifyActiveStaff(
            'application_status_updated',
            'Application status updated',
            'Application ' . $application->application_code . ' is now ' . $statusLabel . '.',
            $application,
            [
                'application_id' => $application->id,
                'application_code' => $application->application_code,
                'old_status' => $oldStatus,
                'new_status' => $application->status,
            ]
        );

        app(NotificationService::class)->notifyLinkedLandownersStatusChanged($application, $statusLabel);

        return back()->with('success', 'Application moved to ' . $statusLabel . '.');
    }

    public function returnForCompliance(Request $request, LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return back()->withErrors(['status' => 'A finalized application cannot be returned for compliance.']);
        }

        if (! in_array($application->status, [
            LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            LandTransferApplication::STATUS_DRAFT,
            LandTransferApplication::STATUS_PENDING_REVIEW,
        ], true)) {
            return back()->withErrors(['status' => 'Return for Compliance is only available during Legal completeness review.']);
        }

        $validated = $request->validate([
            'compliance_reason' => ['required', 'string', 'max:2000'],
        ]);

        $oldStatus = $application->status;
        $application->status = LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE;
        $application->save();

        AuditLogger::record(
            'application_returned_for_compliance',
            $application,
            $application,
            [
                'old_status' => $oldStatus,
                'new_status' => $application->status,
                'compliance_reason' => $validated['compliance_reason'],
                'scope_note' => 'The application remains open for documentary compliance; this is not a final denial.',
            ],
            Auth::id()
        );

        app(NotificationService::class)->notifyLinkedLandownersStatusChanged($application, $application->statusLabel());

        return back()->with('success', 'Application returned for compliance. It remains open and editable.');
    }

    /**
     * PARPO II positive final decision. Approval freezes the application record
     * and creates the immutable Form No. 5 decision output. Physical/administrative
     * release to the client is tracked separately afterward.
     */
    public function approve(Request $request, LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return back()->withErrors(['status' => 'This application already has a final PARPO II decision.']);
        }

        if ($application->status !== LandTransferApplication::STATUS_FOR_RELEASING) {
            return back()->withErrors(['status' => 'Only an application at PARPO II Decision Pending may receive a final approval.']);
        }

        $request->validate([
            'final_decision_confirmation' => ['accepted'],
            'decision_reason' => ['nullable', 'string', 'max:1000'],
            'decision_notes' => ['nullable', 'string', 'max:4000'],
        ], [
            'final_decision_confirmation.accepted' => 'Confirm the final PARPO II approval before continuing.',
        ]);

        [$snapshot, $readinessErrors] = $this->decisionReadiness($application);

        if (! empty($readinessErrors)) {
            return back()->withErrors(array_merge([
                'validation' => 'Resolve the following before recording PARPO II approval:',
            ], $readinessErrors));
        }

        try {
            DB::transaction(function () use ($request, $application, $snapshot) {
                $application = LandTransferApplication::query()
                    ->lockForUpdate()
                    ->findOrFail($application->id);

                if ($application->isFinalized()) {
                    throw new \RuntimeException('This application was already finalized by another request.');
                }

                if ($application->status !== LandTransferApplication::STATUS_FOR_RELEASING) {
                    throw new \RuntimeException('The application status changed before the final decision. Refresh and review the current stage.');
                }

                $application->status = LandTransferApplication::STATUS_APPROVED;
                $application->release_status = LandTransferApplication::RELEASE_NOT_READY;
                $application->reviewed_by = Auth::id();
                $application->reviewed_at = now();
                $application->validated_at = now();
                $application->validation_snapshot = $snapshot;
                $application->decision_reason = $request->input('decision_reason');
                $application->decision_notes = $request->input('decision_notes');
                $application->save();

                AuditLogger::record(
                    'application_approved',
                    $application,
                    $application,
                    [
                        'decision_reason' => $application->decision_reason,
                        'decision_notes' => $application->decision_notes,
                        'validated_at' => optional($application->validated_at)->toDateTimeString(),
                        'form4_recommendation_decision' => $application->ltc_form4_recommendation_decision,
                        'form4_recommendation_matches_final_decision' => $application->ltc_form4_recommendation_decision === 'approval',
                        'ownership_transfer_performed' => false,
                        'registry_mutation_performed' => false,
                        'scope_note' => 'Final DAR clearance decision only. Approval does not execute land ownership transfer or registry alteration.',
                    ],
                    Auth::id()
                );

                app(ApplicationClearanceService::class)->generateForDecision($application, Auth::id());
                app(NotificationService::class)->notifyStaffApplicationApproved($application);
                app(NotificationService::class)->notifyLinkedLandownersFinalDecision($application);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Approval failed: ' . $e->getMessage());
        }

        return back()->with('success', 'PARPO II approval recorded. The final decision is locked; release to the client is tracked separately.');
    }

    /**
     * PARPO II negative final decision. Early documentary deficiencies must use
     * Return for Compliance instead of this final action.
     */
    public function notApproved(Request $request, LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return back()->withErrors(['status' => 'This application already has a final PARPO II decision.']);
        }

        if ($application->status !== LandTransferApplication::STATUS_FOR_RELEASING) {
            return back()->withErrors(['status' => 'Final denial is only available at PARPO II Decision Pending. Use Return for Compliance for intake deficiencies.']);
        }

        $request->validate([
            'final_decision_confirmation' => ['accepted'],
            'decision_reason' => ['required', 'string', 'max:1000'],
            'decision_notes' => ['nullable', 'string', 'max:4000'],
        ], [
            'final_decision_confirmation.accepted' => 'Confirm the final PARPO II denied decision before continuing.',
            'decision_reason.required' => 'A denial reason is required before recording the final Denied decision.',
        ]);

        [$snapshot] = $this->buildValidationSnapshot($application);

        try {
            DB::transaction(function () use ($request, $application, $snapshot) {
                $application = LandTransferApplication::query()
                    ->lockForUpdate()
                    ->findOrFail($application->id);

                if ($application->isFinalized()) {
                    throw new \RuntimeException('This application was already finalized by another request.');
                }

                if ($application->status !== LandTransferApplication::STATUS_FOR_RELEASING) {
                    throw new \RuntimeException('The application status changed before denial. Refresh and review the current stage.');
                }

                $application->status = LandTransferApplication::STATUS_DENIED;
                $application->release_status = LandTransferApplication::RELEASE_NOT_READY;
                $application->reviewed_by = Auth::id();
                $application->reviewed_at = now();
                $application->validated_at = now();
                $application->validation_snapshot = $snapshot;
                $application->decision_reason = $request->input('decision_reason');
                $application->decision_notes = $request->input('decision_notes');
                $application->save();

                $form4Recommendation = $application->ltc_form4_recommendation_decision;

                AuditLogger::record(
                    'application_denied',
                    $application,
                    $application,
                    [
                        'decision_reason' => $application->decision_reason,
                        'decision_notes' => $application->decision_notes,
                        'validated_at' => optional($application->validated_at)->toDateTimeString(),
                        'form4_recommendation_decision' => $form4Recommendation,
                        'form4_recommendation_matches_final_decision' => filled($form4Recommendation)
                            ? $form4Recommendation === 'denial'
                            : null,
                        'ownership_transfer_performed' => false,
                        'registry_mutation_performed' => false,
                        'scope_note' => 'Final DAR clearance decision only. Denial does not alter ownership or registry records.',
                    ],
                    Auth::id()
                );

                app(ApplicationClearanceService::class)->generateForDecision($application, Auth::id());
                app(NotificationService::class)->notifyStaffApplicationDenied($application);
                app(NotificationService::class)->notifyLinkedLandownersFinalDecision($application);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Denied decision failed: ' . $e->getMessage());
        }

        return back()->with('success', 'PARPO II denied decision recorded. The application is now locked.');
    }

    public function markReadyForRelease(Request $request, LandTransferApplication $application)
    {
        if (! $application->isFinalized() || in_array($application->status, LandTransferApplication::LEGACY_FINAL_STATUSES, true)) {
            return back()->withErrors(['status' => 'Only a current Approved or Denied decision may be marked Ready for Release.']);
        }

        if (! $application->clearance()->exists()) {
            return back()->withErrors(['clearance' => 'The final LTC Form No. 5 output must exist before it can be marked Ready for Release.']);
        }

        if ($application->isReleasedToClient()) {
            return back()->withErrors(['release' => 'This decision output has already been released to the client.']);
        }

        $application->release_status = LandTransferApplication::RELEASE_READY;
        $application->ready_for_release_at = $application->ready_for_release_at ?: now();
        $application->save();

        AuditLogger::record(
            'application_ready_for_release',
            $application,
            $application,
            [
                'release_status' => $application->release_status,
                'ready_for_release_at' => optional($application->ready_for_release_at)->toDateTimeString(),
                'scope_note' => 'Administrative delivery readiness only. The final decision remains immutable.',
            ],
            Auth::id()
        );

        app(NotificationService::class)->notifyStaffApplicationReadyForRelease($application);

        return back()->with('success', 'Signed decision output marked Ready for Release.');
    }

    public function release(Request $request, LandTransferApplication $application)
    {
        if (! $application->isFinalized()) {
            return back()->withErrors(['status' => 'A final Approved or Denied decision is required before release to the client.']);
        }

        if (! $application->isReleaseReady()) {
            return back()->withErrors(['release' => 'Mark the signed decision output Ready for Release before recording client release.']);
        }

        $validated = $request->validate([
            'release_confirmation' => ['accepted'],
            'release_recipient_name' => ['required', 'string', 'max:255'],
            'release_logbook_reference' => ['nullable', 'string', 'max:150'],
            'csm_status' => ['nullable', 'in:issued,received,not_recorded'],
        ], [
            'release_confirmation.accepted' => 'Confirm that the signed output was actually released to the client or authorized representative.',
            'release_recipient_name.required' => 'Record the name of the client or authorized representative who received the decision output.',
        ]);

        try {
            DB::transaction(function () use ($validated, $application) {
                $application = LandTransferApplication::query()
                    ->lockForUpdate()
                    ->findOrFail($application->id);

                if (! $application->isFinalized() || $application->release_status !== LandTransferApplication::RELEASE_READY) {
                    throw new \RuntimeException('The release state changed. Refresh the page before recording release.');
                }

                $application->release_status = LandTransferApplication::RELEASED_TO_CLIENT;
                $application->released_at = now();
                $application->released_by = Auth::id();
                $application->release_recipient_name = $validated['release_recipient_name'];
                $application->release_logbook_reference = $validated['release_logbook_reference'] ?? null;
                $application->csm_status = $validated['csm_status'] ?? 'not_recorded';
                $application->date_of_clearance_release = now()->toDateString();
                $application->save();

                AuditLogger::record(
                    'application_released_to_client',
                    $application,
                    $application,
                    [
                        'final_decision_status' => $application->status,
                        'release_status' => $application->release_status,
                        'released_at' => optional($application->released_at)->toDateTimeString(),
                        'release_recipient_name' => $application->release_recipient_name,
                        'release_logbook_reference' => $application->release_logbook_reference,
                        'csm_status' => $application->csm_status,
                        'ownership_transfer_performed' => false,
                        'registry_mutation_performed' => false,
                        'scope_note' => 'Administrative release of the signed clearance decision only. No ownership transfer or registry mutation was performed.',
                    ],
                    Auth::id()
                );

                app(NotificationService::class)->notifyStaffApplicationReleasedToClient($application);
                app(NotificationService::class)->notifyLinkedLandownersReleasedToClient($application);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Release recording failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Release to client recorded. The PARPO II decision remains unchanged and locked.');
    }

    /**
     * Small JSON endpoint used by the existing application page to render the
     * stage-specific fields without duplicating workflow rules in Blade.
     */
    public function state(LandTransferApplication $application)
    {
        $application->loadMissing('clearance');
        $requirements = app(ApplicationRequirementService::class)->evaluate($application);
        $nextStatus = $application->nextWorkflowStatus();

        return response()->json([
            'application_id' => $application->id,
            'application_code' => $application->application_code,
            'status' => $application->status,
            'status_label' => $application->statusLabel(),
            'next_status' => $nextStatus,
            'next_status_label' => $nextStatus ? (LandTransferApplication::statusLabels()[$nextStatus] ?? $nextStatus) : null,
            'is_final' => $application->isFinalized(),
            'release_status' => $application->release_status,
            'release_status_label' => $application->releaseStatusLabel(),
            'filing_fee' => (float) config('dar_ltc.filing_fee', 2000),
            'can_return_for_compliance' => ! $application->isFinalized()
                && in_array($application->status, [LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW, LandTransferApplication::STATUS_DRAFT, LandTransferApplication::STATUS_PENDING_REVIEW], true),
            'can_finalize_decision' => ! $application->isFinalized()
                && $application->status === LandTransferApplication::STATUS_FOR_RELEASING,
            'can_mark_ready_for_release' => $application->isFinalized()
                && ! in_array($application->status, LandTransferApplication::LEGACY_FINAL_STATUSES, true)
                && ! $application->isReleasedToClient()
                && $application->release_status !== LandTransferApplication::RELEASE_READY
                && (bool) $application->clearance,
            'can_release_output' => $application->isReleaseReady(),
            'form4_editable' => $application->canEditForm4(),
            'applicant_is_juridical_entity' => (bool) $application->applicant_is_juridical_entity,
            'payment_order_reference' => $application->payment_order_reference,
            'or_number' => $application->or_number,
            'or_date' => optional($application->or_date)->toDateString(),
            'amount_paid' => $application->amount_paid !== null ? (float) $application->amount_paid : null,
            'csw_reference' => $application->csw_reference,
            'csw_notes' => $application->csw_notes,
            'released_at' => optional($application->released_at)->toDateTimeString(),
            'release_recipient_name' => $application->release_recipient_name,
            'release_logbook_reference' => $application->release_logbook_reference,
            'csm_status' => $application->csm_status,
            'requirements' => $requirements,
        ]);
    }

    private function decisionReadiness(LandTransferApplication $application): array
    {
        [$snapshot, $hasCriticalFailures, $validationMessages] = $this->buildValidationSnapshot($application);
        $workflow = $snapshot['workflow_readiness'];
        $errors = [];

        if (! $workflow['linked_parties_complete']) {
            $errors['parties'] = 'Every transferor and transferee must be linked to a Landowner record before PARPO II decision.';
        }

        if (! $workflow['has_linked_parcel']) {
            $errors['parcel'] = 'At least one valid Parcel record must be linked before PARPO II decision.';
        }

        if (! $workflow['payment_complete']) {
            $errors['payment'] = 'The Payment Order and Official Receipt details must be complete and match the configured filing fee.';
        }

        if (! $workflow['form4_complete']) {
            $missing = implode(', ', $workflow['form4_missing_items']);
            $errors['form4'] = 'Complete LTC Form No. 4 before PARPO II decision.'
                . ($missing !== '' ? ' Missing: ' . $missing . '.' : '');
        }

        if (! $workflow['csw_complete']) {
            $errors['csw'] = 'Completed Staff Work must be recorded before PARPO II decision.';
        }

        if ($hasCriticalFailures) {
            $errors = array_merge($errors, $validationMessages);
        }

        return [$snapshot, $errors];
    }

    /**
     * Build an audit-ready snapshot at the final-decision gate.
     */
    private function buildValidationSnapshot(LandTransferApplication $application): array
    {
        $hectareValidation = app(LandholdingAreaValidationService::class)->forApplication($application);
        $requirements = app(ApplicationRequirementService::class)->evaluate($application);
        $validationMessages = $requirements['errors'];

        $hectareBlocks = (bool) ($hectareValidation['blocks_release'] ?? $hectareValidation['exceeds_limit'] ?? false);

        if ($hectareBlocks) {
            if ((bool) ($hectareValidation['retention_certificate_missing'] ?? false)) {
                $validationMessages['retention_certificate'] = 'Retention Certificate is marked as required, but no retention certificate reference was recorded.';
            } elseif ((bool) ($hectareValidation['exceeds_limit'] ?? false)) {
                $validationMessages['five_hectare'] = sprintf(
                    'Projected landholding total exceeds the 5-hectare reference limit: %s ha projected against %s ha limit.',
                    number_format((float) ($hectareValidation['projected_total'] ?? 0), 4),
                    number_format((float) ($hectareValidation['limit'] ?? 5), 4)
                );
            }
        }

        $hasCriticalFailures = ! $requirements['complete'] || $hectareBlocks;
        $workflowReadiness = $this->workflowReadinessSnapshot($application);

        $snapshot = [
            'computed_at' => now()->toDateTimeString(),
            'workflow_readiness' => $workflowReadiness,
            'requirements' => $requirements,
            'five_hectare' => [
                'current_approved_total' => (float) ($hectareValidation['current_active_total'] ?? 0),
                'pending_incoming_total' => (float) ($hectareValidation['pending_incoming_total'] ?? 0),
                'this_application_total' => (float) ($hectareValidation['this_application_total'] ?? 0),
                'projected_total' => (float) ($hectareValidation['projected_total'] ?? 0),
                'remaining_after_projection' => (float) ($hectareValidation['remaining_after_projection'] ?? 0),
                'exceeds_limit' => (bool) ($hectareValidation['exceeds_limit'] ?? false),
                'succession_exception_claimed' => (bool) ($hectareValidation['succession_exception_claimed'] ?? false),
                'retention_certificate_required' => (bool) ($hectareValidation['retention_certificate_required'] ?? false),
                'retention_certificate_reference' => $hectareValidation['retention_certificate_reference'] ?? null,
                'retention_certificate_missing' => (bool) ($hectareValidation['retention_certificate_missing'] ?? false),
                'blocks_release' => $hectareBlocks,
                'limit' => (float) ($hectareValidation['limit'] ?? 5),
                'scope_note' => $hectareValidation['scope_note'] ?? 'Assistive landholding validation only.',
                'per_landowner' => $hectareValidation['per_landowner'] ?? [],
            ],
            'scope_note' => 'Decision-readiness checks support DAR review only. They do not execute or finalize legal land ownership transfer.',
        ];

        return [$snapshot, $hasCriticalFailures, $validationMessages];
    }

    private function workflowReadinessSnapshot(LandTransferApplication $application): array
    {
        $linkedParcelCount = $application->applicationParcels()
            ->whereNotNull('parcel_id')
            ->whereHas('parcel')
            ->count();

        $subjectFindings = collect((array) $application->ltc_form4_subject_land_findings)
            ->filter(fn ($value) => filled($value));
        $recommendationFindings = collect((array) $application->ltc_form4_recommendation_findings)
            ->filter(fn ($value) => filled($value));
        $hasMeaningfulFindings = $subjectFindings->isNotEmpty()
            || $recommendationFindings->isNotEmpty()
            || filled($application->ltc_form4_other_findings);

        $form4MissingItems = [];

        if (! filled($application->ltc_form4_recommendation_decision)) {
            $form4MissingItems[] = 'recommendation decision';
        }
        if (! $application->ltc_form4_certified_at) {
            $form4MissingItems[] = 'certification date';
        }
        if (! filled($application->ltc_form4_certifying_officer_name)) {
            $form4MissingItems[] = 'authorized officer';
        }
        if (! $hasMeaningfulFindings) {
            $form4MissingItems[] = 'review finding';
        }

        $expectedFee = (float) config('dar_ltc.filing_fee', 2000);
        $paymentComplete = filled($application->payment_order_reference)
            && $application->payment_order_issued_at
            && filled($application->or_number)
            && $application->or_date
            && $application->amount_paid !== null
            && abs(((float) $application->amount_paid) - $expectedFee) <= 0.009;

        return [
            'linked_parties_complete' => $application->allPartiesLinked(),
            'linked_parcel_count' => $linkedParcelCount,
            'has_linked_parcel' => $linkedParcelCount > 0,
            'payment_complete' => $paymentComplete,
            'expected_filing_fee' => $expectedFee,
            'form4_complete' => empty($form4MissingItems),
            'form4_missing_items' => $form4MissingItems,
            'form4_recommendation_decision' => $application->ltc_form4_recommendation_decision,
            'csw_complete' => filled($application->csw_reference) && (bool) $application->csw_completed_at,
            'csw_reference' => $application->csw_reference,
            'scope_note' => 'Workflow readiness verifies administrative record completeness only. Form No. 4 remains recommendatory and no ownership transfer or registry mutation is performed.',
        ];
    }
}
