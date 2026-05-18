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

        $application->load('clearance');

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

        return view('landowner.clearances.show', [
            'application' => $application,
            'clearance' => $application->clearance,
        ]);
    }

    public function pdf(LandTransferApplication $application)
    {
        $this->authorizeLandownerApplication($application);

        $application->load('clearance');

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

        $pdf = Pdf::loadView('staff.clearances.pdf', [
            'application' => $application,
            'clearance' => $application->clearance,
        ])->setPaper('a4');

        return $pdf->stream($application->clearance->clearance_number . '.pdf');
    }

    private function authorizeLandownerApplication(LandTransferApplication $application): void
    {
        $landownerIds = Landowner::query()
            ->where('user_id', Auth::id())
            ->pluck('id');

        abort_unless(
            $landownerIds->contains($application->transferor_landowner_id)
                || $landownerIds->contains($application->transferee_landowner_id),
            403
        );
    }
}
