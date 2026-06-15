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
     * Advance the application through the DAR office workflow.
     *
     * Existing route/method name is preserved for compatibility, but the action
     * now represents stage advancement, not ownership transfer or registry mutation.
     */
    public function submit(LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return back()->withErrors(['status' => 'This application is already finalized and cannot be advanced again.']);
        }

        $oldStatus = $application->status;

        $nextStatus = match ($application->status) {
            LandTransferApplication::STATUS_DRAFT,
            LandTransferApplication::STATUS_PENDING_REVIEW => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            default => $application->nextWorkflowStatus(),
        };

        if (! $nextStatus || $nextStatus === LandTransferApplication::STATUS_RELEASED) {
            return back()->withErrors(['status' => 'This application cannot be advanced further through this action. Use the release action when the clearance is ready for release.']);
        }

        $application->status = $nextStatus;
        $application->save();

        AuditLogger::record(
            'application_status_advanced',
            $application,
            $application,
            [
                'old_status' => $oldStatus,
                'new_status' => $application->status,
                'scope_note' => 'Status advancement only. No ownership transfer or registry mutation performed.',
            ]
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

    /**
     * Release: for_releasing -> released
     *
     * Scope rule:
     * Releasing generates and records a clearance result only.
     * It does NOT automatically transfer land ownership or mutate registry records.
     */
    public function approve(Request $request, LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return back()->withErrors(['status' => 'This application already has a final decision and cannot be released again.']);
        }

        if (! in_array($application->status, [
            LandTransferApplication::STATUS_FOR_RELEASING,
            LandTransferApplication::STATUS_PENDING_REVIEW,
        ], true)) {
            return back()->withErrors(['status' => 'Only applications marked For Releasing can be released.']);
        }

        $approvalErrors = [];

        if (! $application->transferor_landowner_id) {
            $approvalErrors['transferor_landowner_id'] = 'Transferor must be linked to an existing Landowner record before release.';
        }

        if (! $application->transferee_landowner_id) {
            $approvalErrors['transferee_landowner_id'] = 'Transferee must be linked to an existing Landowner record before release.';
        }

        if (! empty($approvalErrors)) {
            return back()->withErrors($approvalErrors);
        }

        [$snapshot, $hasCriticalFailures, $validationMessages] = $this->buildValidationSnapshot($application);

        if ($hasCriticalFailures) {
            $validationMessages = array_merge([
                'validation' => 'Resolve the following before releasing this clearance:',
            ], $validationMessages);

            return back()->withErrors($validationMessages);
        }

        try {
            DB::transaction(function () use ($request, $application, $snapshot) {
                $application = LandTransferApplication::findOrFail($application->id);

                $application->status = LandTransferApplication::STATUS_RELEASED;
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
                    'application_released',
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

                app(NotificationService::class)->notifyStaffApplicationReleased($application);
                app(NotificationService::class)->notifyLinkedLandownersFinalDecision($application);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Release failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Application released and clearance generated.');
    }

    /**
     * Deny: active status -> denied
     * Requires a reason/remarks and creates a final decision record.
     */
    public function notApproved(Request $request, LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return back()->withErrors(['status' => 'This application already has a final decision and cannot be denied again.']);
        }

        if (! in_array($application->status, array_merge(
            LandTransferApplication::ACTIVE_STATUSES,
            [LandTransferApplication::STATUS_DRAFT, LandTransferApplication::STATUS_PENDING_REVIEW]
        ), true)) {
            return back()->withErrors(['status' => 'Only active applications can be denied.']);
        }

        $request->validate([
            'decision_reason' => ['required', 'string', 'max:1000'],
            'decision_notes' => ['nullable', 'string', 'max:4000'],
        ], [
            'decision_reason.required' => 'A denial reason is required before marking the application as Denied.',
        ]);

        [$snapshot] = $this->buildValidationSnapshot($application);

        try {
            DB::transaction(function () use ($request, $application, $snapshot) {
                $application = LandTransferApplication::findOrFail($application->id);
                $application->status = LandTransferApplication::STATUS_DENIED;
                $application->reviewed_by = Auth::id();
                $application->reviewed_at = now();
                $application->validated_at = now();
                $application->validation_snapshot = $snapshot;
                $application->decision_reason = $request->input('decision_reason');
                $application->decision_notes = $request->input('decision_notes');
                $application->registry_mutated_at = null;
                $application->registry_mutated_by = null;
                $application->save();

                AuditLogger::record(
                    'application_denied',
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

                app(NotificationService::class)->notifyStaffApplicationDenied($application);
                app(NotificationService::class)->notifyLinkedLandownersFinalDecision($application);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Denied decision failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Application marked as Denied and decision record generated.');
    }

    /**
     * Build an audit-ready snapshot of validations at decision time.
     *
     * Returns: [snapshotArray, hasCriticalFailuresBool, validationMessagesArray]
     */
    private function buildValidationSnapshot(LandTransferApplication $application): array
    {
        $hectareValidation = app(LandholdingAreaValidationService::class)
            ->forApplication($application);

        $mandatoryDocuments = RequiredDocument::query()
            ->acceptanceBlocking()
            ->orderBy('id')
            ->get(['id', 'name', 'applies_to', 'requirement_classification', 'blocks_acceptance']);

        $uploadedIds = $application->documents()
            ->whereNotNull('file_path')
            ->pluck('required_document_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $missingMandatoryDocuments = $mandatoryDocuments
            ->reject(fn ($document) => in_array((int) $document->id, $uploadedIds, true))
            ->values();

        $missingMandatoryCount = $missingMandatoryDocuments->count();

        $validationMessages = [];

        if ((bool) ($hectareValidation['blocks_release'] ?? $hectareValidation['exceeds_limit'])) {
            if ((bool) ($hectareValidation['retention_certificate_missing'] ?? false)) {
                $validationMessages['retention_certificate'] = 'Retention Certificate is marked as required, but no retention certificate reference was recorded.';
            } elseif ((bool) $hectareValidation['exceeds_limit']) {
                $validationMessages['five_hectare'] = sprintf(
                    'Projected landholding total exceeds the 5-hectare reference limit: %s ha projected against %s ha limit.',
                    number_format((float) $hectareValidation['projected_total'], 4),
                    number_format((float) $hectareValidation['limit'], 4)
                );
            }
        }

        foreach ($missingMandatoryDocuments as $document) {
            $partyLabel = ucfirst((string) $document->applies_to);
            $validationMessages['missing_document_' . $document->id] = "Missing required {$partyLabel} document: {$document->name}.";
        }

        $missingMandatoryByParty = $missingMandatoryDocuments
            ->groupBy('applies_to')
            ->map(fn ($documents) => $documents
                ->map(fn ($document) => [
                    'id' => (int) $document->id,
                    'name' => $document->name,
                ])
                ->values()
                ->all()
            )
            ->all();

        $hasCriticalFailures = (bool) ($hectareValidation['blocks_release'] ?? $hectareValidation['exceeds_limit']) || $missingMandatoryCount > 0;

        $snapshot = [
            'computed_at' => now()->toDateTimeString(),
            'five_hectare' => [
                'current_approved_total' => (float) $hectareValidation['current_active_total'],
                'pending_incoming_total' => (float) $hectareValidation['pending_incoming_total'],
                'this_application_total' => (float) $hectareValidation['this_application_total'],
                'projected_total' => (float) $hectareValidation['projected_total'],
                'remaining_after_projection' => (float) $hectareValidation['remaining_after_projection'],
                'exceeds_limit' => (bool) $hectareValidation['exceeds_limit'],
                'succession_exception_claimed' => (bool) ($hectareValidation['succession_exception_claimed'] ?? false),
                'retention_certificate_required' => (bool) ($hectareValidation['retention_certificate_required'] ?? false),
                'retention_certificate_reference' => $hectareValidation['retention_certificate_reference'] ?? null,
                'retention_certificate_missing' => (bool) ($hectareValidation['retention_certificate_missing'] ?? false),
                'blocks_release' => (bool) ($hectareValidation['blocks_release'] ?? $hectareValidation['exceeds_limit']),
                'limit' => (float) $hectareValidation['limit'],
                'scope_note' => $hectareValidation['scope_note'],
            ],
            'documents' => [
                'classification_scope' => 'Only acceptance/release-blocking documents are counted as critical blockers. Case-dependent and reference-only documents remain visible for manual review.',
                'missing_mandatory_count' => $missingMandatoryCount,
                'missing_mandatory_ids' => $missingMandatoryDocuments->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'missing_mandatory_by_party' => $missingMandatoryByParty,
                'missing_mandatory_names' => $missingMandatoryDocuments->pluck('name')->values()->all(),
            ],
        ];

        return [$snapshot, $hasCriticalFailures, $validationMessages];
    }
}
