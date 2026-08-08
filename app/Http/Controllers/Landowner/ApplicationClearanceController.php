<?php

namespace App\Http\Controllers\Landowner;

use App\Http\Controllers\Controller;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Services\ClearanceBrowserPdfRenderer;
use Illuminate\Support\Facades\Auth;

class ApplicationClearanceController extends Controller
{
    public function show(LandTransferApplication $application)
    {
        $this->authorizeLandownerApplication($application);
        $application->load(['clearance']);

        if (! $application->isFinalized()) {
            return redirect()
                ->route('landowner.applications.index')
                ->with('error', 'Decision output is only available after the application is finalized.');
        }

        if (! $application->clearance) {
            return redirect()
                ->route('landowner.applications.index')
                ->with('error', 'Decision output record is not yet available for this application.');
        }

        return redirect()->route('landowner.applications.clearance.pdf', $application);
    }

    public function pdf(LandTransferApplication $application, ClearanceBrowserPdfRenderer $renderer)
    {
        $this->authorizeLandownerApplication($application);
        $application->load(['clearance', 'documents.requiredDocument', 'applicationParcels.parcel']);

        if (! $application->isFinalized()) {
            return redirect()
                ->route('landowner.applications.index')
                ->with('error', 'Decision output is only available after the application is finalized.');
        }

        if (! $application->clearance) {
            return redirect()
                ->route('landowner.applications.index')
                ->with('error', 'Decision output record is not yet available for this application.');
        }

        $safeApplicationCode = str_replace(['/', '\\', ' '], '-', (string) $application->application_code);
        $filename = 'LTC-Form-No-5-' . $safeApplicationCode . '.pdf';

        $html = view('staff.clearances.pdf', [
            'application' => $application,
            'clearance' => $application->clearance,
        ])->render();

        $pdf = $renderer->render($html);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($pdf),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function authorizeLandownerApplication(LandTransferApplication $application): void
    {
        $landownerIds = Landowner::query()
            ->where('user_id', Auth::id())
            ->pluck('id');

        abort_unless(
            $landownerIds->contains(fn ($landownerId) => $application->isLinkedToLandowner((int) $landownerId)),
            403
        );
    }
}
