<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Models\ApplicationDocument;
use Barryvdh\DomPDF\Facade\Pdf;

class ApplicationClearanceController extends Controller
{
    public function show(LandTransferApplication $application)
    {
        $application->load('clearance');

        if (! $application->isFinalized()) {
            return back()->with('error', 'Decision output is only available for released or denied applications.');
        }

        if (! $application->clearance) {
            return back()->with('error', 'Decision output record not found for this application.');
        }

        return view('staff.clearances.show', [
            'application' => $application,
            'clearance' => $application->clearance,
        ]);
    }

    public function pdf(LandTransferApplication $application)
    {
        $application->load('clearance');

        if (! $application->isFinalized()) {
            return back()->with('error', 'Decision output is only available for released or denied applications.');
        }

        if (! $application->clearance) {
            return back()->with('error', 'Decision output record not found for this application.');
        }

        $pdf = Pdf::loadView('staff.clearances.pdf', [
            'application' => $application,
            'clearance' => $application->clearance,
        ])->setPaper('a4');

        return $pdf->stream($application->clearance->clearance_number . '.pdf');
    }
    public function acknowledgementPdf(LandTransferApplication $application)
    {
        $application->load([
            'documents.requiredDocument',
            'transferorLandowner',
            'transfereeLandowner',
        ]);

        $transferorRequirements = RequiredDocument::where('applies_to', 'transferor')
            ->orderBy('blocks_acceptance', 'desc')
            ->orderBy('requirement_classification')
            ->orderBy('name')
            ->get();

        $transfereeRequirements = RequiredDocument::where('applies_to', 'transferee')
            ->orderBy('blocks_acceptance', 'desc')
            ->orderBy('requirement_classification')
            ->orderBy('name')
            ->get();

        $uploaded = ApplicationDocument::where('land_transfer_application_id', $application->id)
            ->get()
            ->keyBy('required_document_id');

        $allRequirements = $transferorRequirements->concat($transfereeRequirements);
        $blockingRequirements = $allRequirements->filter(
            fn ($requirement) => method_exists($requirement, 'blocksAcceptance')
                ? $requirement->blocksAcceptance()
                : (bool) $requirement->is_mandatory
        );

        $pdf = Pdf::loadView('staff.applications.pdfs.acknowledgement-receipt', [
            'application' => $application,
            'transferorRequirements' => $transferorRequirements,
            'transfereeRequirements' => $transfereeRequirements,
            'uploaded' => $uploaded,
            'blockingRequirements' => $blockingRequirements,
        ])->setPaper('a4');

        $safeApplicationCode = str_replace(['/', '\\', ' '], '-', (string) $application->application_code);

        return $pdf->stream('LTC-Form-No-3-' . $safeApplicationCode . '.pdf');
    }

    public function form4Pdf(LandTransferApplication $application)
    {
        $application->load([
            'applicationParcels.parcel',
            'transferorLandowner',
            'transfereeLandowner',
        ]);

        $pdf = Pdf::loadView('staff.applications.pdfs.form4-attestation-recommendation', [
            'application' => $application,
        ])->setPaper('a4');

        $safeApplicationCode = str_replace(['/', '\\', ' '], '-', (string) $application->application_code);

        return $pdf->stream('LTC-Form-No-4-' . $safeApplicationCode . '.pdf');
    }

}
