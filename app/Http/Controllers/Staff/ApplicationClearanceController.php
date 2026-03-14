<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\LandTransferApplication;
use Barryvdh\DomPDF\Facade\Pdf;

class ApplicationClearanceController extends Controller
{
    public function show(LandTransferApplication $application)
    {
        $application->load('clearance');

        if (!in_array($application->status, ['approved', 'not_approved'], true)) {
            return back()->with('error', 'Clearance is only available for finalized applications.');
        }

        if (!$application->clearance) {
            return back()->with('error', 'Clearance record not found for this application.');
        }

        return view('staff.clearances.show', [
            'application' => $application,
            'clearance' => $application->clearance,
        ]);
    }

    public function pdf(LandTransferApplication $application)
    {
        $application->load('clearance');

        if (!in_array($application->status, ['approved', 'not_approved'], true)) {
            return back()->with('error', 'Clearance is only available for finalized applications.');
        }

        if (!$application->clearance) {
            return back()->with('error', 'Clearance record not found for this application.');
        }

        $pdf = Pdf::loadView('staff.clearances.pdf', [
            'application' => $application,
            'clearance' => $application->clearance,
        ])->setPaper('a4');

        return $pdf->stream($application->clearance->clearance_number . '.pdf');
    }
}