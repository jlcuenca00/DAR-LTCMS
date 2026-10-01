<?php

namespace App\Http\Controllers\Landowner;

use App\Http\Controllers\Controller;
use App\Models\LandTransferApplication;
use App\Services\ApplicationClearanceIntegrityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class ApplicationClearanceController extends Controller
{
    public function show(LandTransferApplication $application)
    {
        Gate::authorize('viewDecisionOutput', $application);
        $application->load('clearance');

        if (! $application->isFinalized()) {
            return redirect()
                ->route('landowner.applications.index')
                ->with('error', 'Decision output is only available after the application receives a final PARPO II decision.');
        }

        if (! $application->isReleasedToClient()) {
            return redirect()
                ->route('landowner.applications.index')
                ->with('error', 'The final decision has been recorded, but the signed output has not yet been released to the client.');
        }

        if (! $application->clearance) {
            return redirect()
                ->route('landowner.applications.index')
                ->with('error', 'Decision output record is not available for this application.');
        }

        $integrity = app(ApplicationClearanceIntegrityService::class)->inspect($application);
        if (! $integrity['valid']) {
            return redirect()
                ->route('landowner.applications.index')
                ->with('error', 'The released decision output is unavailable because its integrity check requires DAR staff review.');
        }

        return view('staff.clearances.show', [
            'application' => $application,
            'clearance' => $application->clearance,
            'returnRoute' => route('landowner.applications.index'),
            'returnLabel' => 'Back to Applications',
        ]);
    }

    public function pdf(LandTransferApplication $application)
    {
        Gate::authorize('viewDecisionOutput', $application);
        $application->load('clearance');

        if (! $application->isFinalized()) {
            return redirect()
                ->route('landowner.applications.index')
                ->with('error', 'Decision output is only available after the application receives a final PARPO II decision.');
        }

        if (! $application->isReleasedToClient()) {
            return redirect()
                ->route('landowner.applications.index')
                ->with('error', 'The final decision has been recorded, but the signed output has not yet been released to the client.');
        }

        if (! $application->clearance) {
            return redirect()
                ->route('landowner.applications.index')
                ->with('error', 'Decision output record is not available for this application.');
        }

        $integrity = app(ApplicationClearanceIntegrityService::class)->inspect($application);
        if (! $integrity['valid']) {
            return redirect()
                ->route('landowner.applications.index')
                ->with('error', 'The released decision output is unavailable because its integrity check requires DAR staff review.');
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

}
