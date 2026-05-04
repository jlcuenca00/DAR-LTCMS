<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ApplicationDocumentController extends Controller
{
    public function store(Request $request, LandTransferApplication $application, RequiredDocument $requiredDocument)
    {
        if ($application->isFinalized()) {
            return redirect()
                ->route('staff.applications.show', ['application' => $application->id])
                ->with('error', 'This application is already finalized. Document uploads are locked.');
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'annex_reference' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $path = $request->file('file')->store("application-documents/{$application->id}");

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

        return redirect()
            ->route('staff.applications.show', ['application' => $application->id])
            ->with('success', 'Document uploaded successfully.');
    }

    public function destroy(LandTransferApplication $application, RequiredDocument $requiredDocument)
    {
        if ($application->isFinalized()) {
            return redirect()
                ->route('staff.applications.show', ['application' => $application->id])
                ->with('error', 'This application is finalized. Documents cannot be removed.');
        }

        $document = ApplicationDocument::where('land_transfer_application_id', $application->id)
            ->where('required_document_id', $requiredDocument->id)
            ->first();

        if (! $document) {
            return back()->with('error', 'Document not found.');
        }

        if ($document->file_path && Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
        }

        $document->delete();

        return redirect()
            ->route('staff.applications.show', ['application' => $application->id])
            ->with('success', 'Document removed successfully.');
    }
}