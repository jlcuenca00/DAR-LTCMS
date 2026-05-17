<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\LandTransferApplication;
use App\Models\LegacyRecord;
use App\Models\RequiredDocument;
use App\Models\SourceRecordPackage;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ApplicationDocumentController extends Controller
{
    public function store(Request $request, LandTransferApplication $application, RequiredDocument $requiredDocument)
    {
        if ($application->isFinalized()) {
            return redirect()
                ->route('staff.applications.show', ['application' => $application->id])
                ->with('error', 'This application is already finalized. Document uploads and metadata encoding are locked.');
        }

        $existingDocument = ApplicationDocument::where('land_transfer_application_id', $application->id)
            ->where('required_document_id', $requiredDocument->id)
            ->first();

        $validated = $request->validate([
            'file' => [$existingDocument ? 'nullable' : 'required', 'file', 'max:10240'],
            'annex_reference' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'source_record_link' => ['nullable', 'string', 'max:100'],

            'document_reference_number' => ['nullable', 'string', 'max:150'],

            'document_metadata' => ['nullable', 'array'],
            'document_metadata.title_number' => ['nullable', 'string', 'max:150'],
            'document_metadata.tax_declaration_number' => ['nullable', 'string', 'max:150'],
            'document_metadata.document_number' => ['nullable', 'string', 'max:150'],
            'document_metadata.issuing_office' => ['nullable', 'string', 'max:255'],
            'document_metadata.date_issued' => ['nullable', 'date'],
            'document_metadata.reference_lot_or_parcel' => ['nullable', 'string', 'max:255'],
            'document_metadata.verification_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        [$sourceRecordId, $sourceRecordPackageId] = $this->resolveSourceRecordLink($validated['source_record_link'] ?? null);

        $metadata = $this->cleanMetadata($validated['document_metadata'] ?? []);

        $hasMetadata = filled($validated['document_reference_number'] ?? null) || ! empty($metadata);

        $documentValues = [
            'annex_reference' => $validated['annex_reference'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'source_record_id' => $sourceRecordId,
            'source_record_package_id' => $sourceRecordPackageId,
            'document_reference_number' => $validated['document_reference_number'] ?? null,
            'document_metadata' => $metadata ?: null,
            'metadata_encoded_by' => $hasMetadata ? Auth::id() : null,
            'metadata_encoded_at' => $hasMetadata ? now() : null,
        ];

        if ($request->hasFile('file')) {
            if ($existingDocument?->file_path && Storage::exists($existingDocument->file_path)) {
                Storage::delete($existingDocument->file_path);
            }

            $path = $request->file('file')->store("application-documents/{$application->id}");

            $documentValues['original_filename'] = $request->file('file')->getClientOriginalName();
            $documentValues['file_path'] = $path;
            $documentValues['uploaded_by'] = Auth::id();
        } elseif ($existingDocument) {
            $documentValues['original_filename'] = $existingDocument->original_filename;
            $documentValues['file_path'] = $existingDocument->file_path;
            $documentValues['uploaded_by'] = $existingDocument->uploaded_by;
        }

        $document = ApplicationDocument::updateOrCreate(
            [
                'land_transfer_application_id' => $application->id,
                'required_document_id' => $requiredDocument->id,
            ],
            $documentValues
        );

        $action = $existingDocument
            ? ($request->hasFile('file') ? 'document_replaced' : 'document_metadata_updated')
            : 'document_uploaded';

        AuditLogger::record(
            $action,
            $application,
            $document,
            [
                'required_document_id' => $requiredDocument->id,
                'required_document_name' => $requiredDocument->name,
                'original_filename' => $document->original_filename,
                'annex_reference' => $document->annex_reference,
                'document_reference_number' => $document->document_reference_number,
                'document_metadata' => $document->document_metadata,
                'source_record_id' => $document->source_record_id,
                'source_record_package_id' => $document->source_record_package_id,
                'file_replaced' => $request->hasFile('file'),
                'scope_note' => 'Document/source record linking is for traceability and review only. No ownership transfer or registry mutation was performed.',
            ]
        );

        return redirect()
            ->route('staff.applications.show', ['application' => $application->id])
            ->with('success', $request->hasFile('file')
                ? 'Document file and indexing details saved successfully.'
                : 'Document indexing details saved successfully. The existing uploaded file was kept.');
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

        AuditLogger::record(
            'document_removed',
            $application,
            $document,
            [
                'required_document_id' => $requiredDocument->id,
                'required_document_name' => $requiredDocument->name,
                'original_filename' => $document->original_filename,
                'file_path' => $document->file_path,
                'document_reference_number' => $document->document_reference_number,
                'document_metadata' => $document->document_metadata,
                'source_record_id' => $document->source_record_id,
                'source_record_package_id' => $document->source_record_package_id,
            ]
        );

        $document->delete();

        return redirect()
            ->route('staff.applications.show', ['application' => $application->id])
            ->with('success', 'Document removed successfully.');
    }

    private function cleanMetadata(array $metadata): array
    {
        return collect($metadata)
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn ($value) => filled($value))
            ->toArray();
    }

    private function resolveSourceRecordLink(?string $sourceRecordLink): array
    {
        if (blank($sourceRecordLink)) {
            return [null, null];
        }

        if (! str_contains($sourceRecordLink, ':')) {
            throw ValidationException::withMessages([
                'source_record_link' => 'The selected source record link is invalid.',
            ]);
        }

        [$type, $id] = explode(':', $sourceRecordLink, 2);

        if (! ctype_digit((string) $id)) {
            throw ValidationException::withMessages([
                'source_record_link' => 'The selected source record link is invalid.',
            ]);
        }

        $id = (int) $id;

        if ($type === 'record') {
            if (! LegacyRecord::whereKey($id)->exists()) {
                throw ValidationException::withMessages([
                    'source_record_link' => 'The selected source record does not exist.',
                ]);
            }

            return [$id, null];
        }

        if ($type === 'package') {
            if (! SourceRecordPackage::whereKey($id)->exists()) {
                throw ValidationException::withMessages([
                    'source_record_link' => 'The selected source record package does not exist.',
                ]);
            }

            return [null, $id];
        }

        throw ValidationException::withMessages([
            'source_record_link' => 'The selected source record link is invalid.',
        ]);
    }
}
