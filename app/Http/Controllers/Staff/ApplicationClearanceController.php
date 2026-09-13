<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Services\ApplicationRequirementService;
use Barryvdh\DomPDF\Facade\Pdf;

class ApplicationClearanceController extends Controller
{
    public function show(LandTransferApplication $application)
    {
        $application->load(['clearance', 'documents']);

        if (! $application->isFinalized()) {
            return back()->with('error', 'Decision output is only available after a final Approved or Denied PARPO II decision.');
        }

        if (! $application->clearance) {
            return back()->with('error', 'Decision output record not found for this application.');
        }

        return view('staff.clearances.show', [
            'application' => $application,
            'clearance' => $application->clearance,
            'returnRoute' => route('staff.applications.show', $application),
            'returnLabel' => 'Back to Application',
        ]);
    }

    public function pdf(LandTransferApplication $application)
    {
        $application->load(['clearance', 'documents']);

        if (! $application->isFinalized()) {
            return back()->with('error', 'Decision output is only available after a final Approved or Denied PARPO II decision.');
        }

        if (! $application->clearance) {
            return back()->with('error', 'Decision output record not found for this application.');
        }

        $safeApplicationCode = str_replace(['/', '\\', ' '], '-', (string) $application->application_code);

        $pdf = Pdf::setOption([
            'defaultMediaType' => 'print',
            'defaultFont' => 'Helvetica',
            'isRemoteEnabled' => false,
        ])->loadView('staff.clearances.pdf', [
            'application' => $application,
            'clearance' => $application->clearance,
        ])->setPaper([0, 0, 612, 936]);

        return $pdf->stream('LTC-Form-No-5-' . $safeApplicationCode . '.pdf');
    }

    public function acknowledgementPdf(LandTransferApplication $application)
    {
        $application->load([
            'documents.requiredDocument',
            'applicationParcels.parcel',
            'transferorLandowner',
            'transfereeLandowner',
        ]);

        $requirementService = app(ApplicationRequirementService::class);
        $evaluation = $requirementService->evaluate($application);
        $applicableIds = collect($evaluation['requirements'])
            ->where('applicable', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $blockingIds = collect($evaluation['requirements'])
            ->where('blocking', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $transferorRequirements = RequiredDocument::deduplicateForApplicationReview(
            RequiredDocument::where('applies_to', 'transferor')
                ->whereIn('id', $applicableIds)
                ->orderBy('blocks_acceptance', 'desc')
                ->orderBy('requirement_classification')
                ->orderBy('name')
                ->get()
        );

        $transfereeRequirements = RequiredDocument::deduplicateForApplicationReview(
            RequiredDocument::where('applies_to', 'transferee')
                ->whereIn('id', $applicableIds)
                ->orderBy('blocks_acceptance', 'desc')
                ->orderBy('requirement_classification')
                ->orderBy('name')
                ->get()
        );

        $uploaded = ApplicationDocument::where('land_transfer_application_id', $application->id)
            ->get()
            ->keyBy('required_document_id');

        $allRequirements = $transferorRequirements->concat($transfereeRequirements);
        $blockingRequirements = $allRequirements
            ->filter(fn ($requirement) => in_array((int) $requirement->id, $blockingIds, true))
            ->values();

        $pdf = Pdf::loadView('staff.applications.pdfs.acknowledgement-receipt', [
            'application' => $application,
            'transferorRequirements' => $transferorRequirements,
            'transfereeRequirements' => $transfereeRequirements,
            'uploaded' => $uploaded,
            'blockingRequirements' => $blockingRequirements,
            'requirementEvaluation' => $evaluation,
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
