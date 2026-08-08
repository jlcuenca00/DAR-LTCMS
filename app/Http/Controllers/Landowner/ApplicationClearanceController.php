<?php

namespace App\Http\Controllers\Landowner;

use App\Http\Controllers\Controller;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use Barryvdh\DomPDF\Facade\Pdf;
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

    public function pdf(LandTransferApplication $application)
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

        $pdf = Pdf::setOption([
            'isRemoteEnabled' => true,
            'allowedRemoteHosts' => [
                'raw.githubusercontent.com',
            ],
        ])->loadView('staff.clearances.pdf', [
            'application' => $application,
            'clearance' => $application->clearance,
        ])->setPaper('a4');

        return $pdf->stream('LTC-Form-No-5-' . $safeApplicationCode . '.pdf');
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
