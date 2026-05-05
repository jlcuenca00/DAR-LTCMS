<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\ApplicationParcel;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Models\AuditLog;

class LandTransferApplicationController extends Controller
{
    public function show(LandTransferApplication $application)
    {
        $application->load([
            'documents',
            'applicationParcels.parcel',
            'transferorLandowner',
            'transfereeLandowner',
            'clearance',
        ]);

        // 1) Required documents (checklist)
        $transferorRequirements = RequiredDocument::where('applies_to', 'transferor')
            ->orderBy('is_mandatory', 'desc')
            ->orderBy('name')
            ->get();

        $transfereeRequirements = RequiredDocument::where('applies_to', 'transferee')
            ->orderBy('is_mandatory', 'desc')
            ->orderBy('name')
            ->get();

        // 2) Uploaded docs for this application (keyed by required_document_id)
        $uploaded = ApplicationDocument::where('land_transfer_application_id', $application->id)
            ->get()
            ->keyBy('required_document_id');

        // 3) 5-hectare validation (assistive)
        $transfereeOwner = null;
        $currentApprovedTotal = 0;
        $pendingIncomingTotal = 0;
        $thisApplicationTotal = 0;
        $projectedTotal = 0;
        $exceedsFiveHectares = false;

        if ($application->transferee_landowner_id) {
            $transfereeOwner = Landowner::find($application->transferee_landowner_id);

            // (1) Approved/active landholdings
            $currentApprovedTotal = Landholding::where('landowner_id', $transfereeOwner->id)
                ->where('status', 'active')
                ->sum('area_hectares');

            // (2) Pending incoming from OTHER applications (draft/pending_review)
            $pendingIncomingTotal = ApplicationParcel::whereHas('application', function ($q) use ($transfereeOwner, $application) {
                    $q->where('transferee_landowner_id', $transfereeOwner->id)
                      ->where('id', '!=', $application->id)
                      ->whereIn('status', ['draft', 'pending_review']);
                })
                ->sum('area_hectares');

            // (3) Current application parcels total
            $thisApplicationTotal = ApplicationParcel::where('land_transfer_application_id', $application->id)
                ->sum('area_hectares');

            $projectedTotal = (float) $currentApprovedTotal
                            + (float) $pendingIncomingTotal
                            + (float) $thisApplicationTotal;

            $exceedsFiveHectares = $projectedTotal > 5.0000;
        }

        $applicationTimeline = AuditLog::with('actor')
            ->where('land_transfer_application_id', $application->id)
            ->oldest()
            ->get();

        return view('staff.applications.show', compact(
            'application',
            'transferorRequirements',
            'transfereeRequirements',
            'uploaded',
            'transfereeOwner',
            'currentApprovedTotal',
            'pendingIncomingTotal',
            'thisApplicationTotal',
            'projectedTotal',
            'exceedsFiveHectares',
            'applicationTimeline',
        ));
    }
}