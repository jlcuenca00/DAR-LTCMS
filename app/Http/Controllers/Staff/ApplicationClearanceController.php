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
        $application->load(['clearance']);

        if (! $application->isFinalized()) {
            return back()->with('error', 'Decision output is only available for released or denied applications.');
        }

        if (! $application->clearance) {
            return back()->with('error', 'Decision output record not found for this application.');
        }

        return redirect()->route('staff.applications.clearance.pdf', $application);
    }

    public function pdf(LandTransferApplication $application)
    {
        $application->load(['clearance', 'documents.requiredDocument', 'applicationParcels.parcel']);

        if (! $application->isFinalized()) {
            return back()->with('error', 'Decision output is only available for released or denied applications.');
        }

        if (! $application->clearance) {
            return back()->with('error', 'Decision output record not found for this application.');
        }

        $safeApplicationCode = str_replace(['/', '\\', ' '], '-', (string) $application->application_code);

        $html = $this->renderClearancePdfHtml($application);
        $pdf = Pdf::loadHTML($html)->setPaper('a4');

        return $pdf->stream('LTC-Form-No-5-' . $safeApplicationCode . '.pdf');
    }

    public function acknowledgementPdf(LandTransferApplication $application)
    {
        $application->load([
            'documents.requiredDocument',
            'transferorLandowner',
            'transfereeLandowner',
        ]);

        $transferorRequirements = RequiredDocument::deduplicateForApplicationReview(
            RequiredDocument::where('applies_to', 'transferor')
                ->orderBy('blocks_acceptance', 'desc')
                ->orderBy('requirement_classification')
                ->orderBy('name')
                ->get()
        );

        $transfereeRequirements = RequiredDocument::deduplicateForApplicationReview(
            RequiredDocument::where('applies_to', 'transferee')
                ->orderBy('blocks_acceptance', 'desc')
                ->orderBy('requirement_classification')
                ->orderBy('name')
                ->get()
        );

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

    private function renderClearancePdfHtml(LandTransferApplication $application): string
    {
        $html = view('staff.clearances.pdf', [
            'application' => $application,
            'clearance' => $application->clearance,
        ])->render();

        $bagongSvgPath = public_path('images/bagong-pilipinas-logo.svg');

        if (is_file($bagongSvgPath)) {
            $svgContents = file_get_contents($bagongSvgPath);

            if ($svgContents !== false) {
                $svgDataUri = 'data:image/svg+xml;base64,' . base64_encode($svgContents);
                $html = preg_replace(
                    '/data:image\/png;base64,[A-Za-z0-9+\/=]+/',
                    $svgDataUri,
                    $html,
                    1
                ) ?? $html;
            }
        }

        return $html;
    }
}
