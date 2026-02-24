<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;

class LandTransferApplicationController extends Controller
{
    public function show(LandTransferApplication $application)
    {
        // Load required documents grouped by party
        $transferorRequirements = RequiredDocument::where('applies_to', 'transferor')
            ->orderBy('is_mandatory', 'desc')
            ->orderBy('name')
            ->get();

        $transfereeRequirements = RequiredDocument::where('applies_to', 'transferee')
            ->orderBy('is_mandatory', 'desc')
            ->orderBy('name')
            ->get();

        // Load uploaded docs for this application keyed by required_document_id
        $uploaded = ApplicationDocument::where('land_transfer_application_id', $application->id)
            ->get()
            ->keyBy('required_document_id');

        return view('staff.applications.show', compact(
            'application',
            'transferorRequirements',
            'transfereeRequirements',
            'uploaded'
        ));
    }
}