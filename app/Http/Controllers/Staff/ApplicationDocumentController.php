<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicationDocumentController extends Controller
{
    public function store(Request $request, LandTransferApplication $application, RequiredDocument $requiredDocument)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB
            'annex_reference' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        // Save the file into storage/app/application-documents/{application_id}
        $path = $request->file('file')->store("application-documents/{$application->id}");

        // Create or replace record for this requirement
        ApplicationDocument::updateOrCreate(
            [
                'land_transfer_application_id' => $application->id,
                'required_document_id' => $requiredDocument->id,
            ],
            [
                'original_filename' => $request->file('file')->getClientOriginalName(),
                'file_path' => $path,
                'annex_reference' => $validated['annex_reference'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'uploaded_by' => Auth::id(),
            ]
        );

        return back()->with('success', 'Document uploaded successfully.');
    }
}