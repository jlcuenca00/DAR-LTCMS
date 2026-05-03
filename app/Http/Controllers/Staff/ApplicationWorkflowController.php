<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationParcel;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Services\ApplicationClearanceService;
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
        if ($application->status !== 'draft') {
            return back()->withErrors(['status' => 'Only draft applications can be submitted.']);
        }

        $application->status = 'pending_review';
        $application->save();

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
    if ($application->status !== 'pending_review') {
        return back()->withErrors(['status' => 'Only applications pending review can be approved.']);
    }

    if (!$application->transferor_landowner_id || !$application->transferee_landowner_id) {
        return back()->with('error', 'Cannot approve: Transferor and Transferee must be linked to Landowner records.');
    }

    [$snapshot, $hasCriticalFailures] = $this->buildValidationSnapshot($application);

    if ($hasCriticalFailures) {
        return back()->withErrors([
            'validation' => 'Approval blocked: critical validation failures detected. Mark as Not Approved or resolve issues.'
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

            app(ApplicationClearanceService::class)->generateForDecision($application, Auth::id());
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

                app(ApplicationClearanceService::class)->generateForDecision($application, Auth::id());
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
        $currentApprovedTotal = 0;
        $pendingIncomingTotal = 0;
        $thisApplicationTotal = 0;
        $projectedTotal = 0;
        $exceedsFive = false;

        if ($application->transferee_landowner_id) {
            $owner = Landowner::find($application->transferee_landowner_id);

            $currentApprovedTotal = Landholding::where('landowner_id', $owner->id)
                ->where('status', 'active')
                ->sum('area_hectares');

            $pendingIncomingTotal = ApplicationParcel::whereHas('application', function ($q) use ($owner, $application) {
                    $q->where('transferee_landowner_id', $owner->id)
                      ->where('id', '!=', $application->id)
                      ->whereIn('status', ['draft', 'pending_review']);
                })
                ->sum('area_hectares');

            $thisApplicationTotal = ApplicationParcel::where('land_transfer_application_id', $application->id)
                ->sum('area_hectares');

            $projectedTotal = (float) $currentApprovedTotal + (float) $pendingIncomingTotal + (float) $thisApplicationTotal;
            $exceedsFive = $projectedTotal > 5.0000;
        }

        $mandatoryIds = RequiredDocument::where('is_mandatory', true)->pluck('id')->all();
        $uploadedIds = $application->documents()->pluck('required_document_id')->all();

        $missingMandatory = array_values(array_diff($mandatoryIds, $uploadedIds));
        $missingMandatoryCount = count($missingMandatory);

        $hasCriticalFailures = $exceedsFive || $missingMandatoryCount > 0;

        $snapshot = [
            'computed_at' => now()->toDateTimeString(),
            'five_hectare' => [
                'current_approved_total' => (float) $currentApprovedTotal,
                'pending_incoming_total' => (float) $pendingIncomingTotal,
                'this_application_total' => (float) $thisApplicationTotal,
                'projected_total' => (float) $projectedTotal,
                'exceeds_limit' => $exceedsFive,
                'limit' => 5.0000,
            ],
            'documents' => [
                'missing_mandatory_count' => $missingMandatoryCount,
                'missing_mandatory_ids' => $missingMandatory,
            ],
        ];

        return [$snapshot, $hasCriticalFailures];
    }
}