<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Landowner;
use App\Models\LegacyRecord;
use App\Models\SourceRecordPackage;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SourceRecordLandownerLinkController extends Controller
{
    public function linkPackage(Request $request, SourceRecordPackage $sourceRecordPackage)
    {
        $data = $request->validate([
            'landowner_id' => ['required', 'exists:landowners,id'],
        ]);

        $landowner = Landowner::findOrFail($data['landowner_id']);

        DB::transaction(function () use ($sourceRecordPackage, $landowner) {
            $sourceRecordPackage->update([
                'landowner_id' => $landowner->id,
            ]);

            $sourceRecordPackage->records()->update([
                'landowner_id' => $landowner->id,
            ]);

            AuditLogger::record(
                'source_record_package_linked_to_landowner',
                null,
                $sourceRecordPackage,
                [
                    'package_code' => $sourceRecordPackage->package_code,
                    'landowner_id' => $landowner->id,
                    'landowner_name' => $landowner->full_name,
                    'records_linked' => $sourceRecordPackage->records()->count(),
                    'scope_note' => 'Administrative source-to-landowner linkage only. No ownership transfer or registry mutation was performed.',
                ]
            );
        });

        return back()->with('success', 'Source package linked to existing landowner record successfully.');
    }

    public function createFromPackage(Request $request, SourceRecordPackage $sourceRecordPackage)
    {
        $data = $this->validateLandownerInput($request);

        DB::transaction(function () use ($sourceRecordPackage, $data) {
            $landowner = Landowner::create($data);

            $sourceRecordPackage->update([
                'landowner_id' => $landowner->id,
            ]);

            $sourceRecordPackage->records()->update([
                'landowner_id' => $landowner->id,
            ]);

            AuditLogger::record(
                'landowner_created_from_source_package',
                null,
                $landowner,
                [
                    'package_code' => $sourceRecordPackage->package_code,
                    'source_record_package_id' => $sourceRecordPackage->id,
                    'landowner_id' => $landowner->id,
                    'landowner_name' => $landowner->full_name,
                    'records_linked' => $sourceRecordPackage->records()->count(),
                    'scope_note' => 'Created an administrative Landowner Record from source/provenance data only. This does not verify ownership, transfer land, or mutate registry records.',
                ]
            );

            AuditLogger::record(
                'source_record_package_linked_to_created_landowner',
                null,
                $sourceRecordPackage,
                [
                    'package_code' => $sourceRecordPackage->package_code,
                    'landowner_id' => $landowner->id,
                    'landowner_name' => $landowner->full_name,
                    'scope_note' => 'Administrative linkage only. No ownership transfer or registry mutation was performed.',
                ]
            );
        });

        return back()->with('success', 'Landowner record created from source package and linked successfully.');
    }


    public function linkSourceRecordPackage(Request $request, SourceRecordPackage $sourceRecordPackage)
    {
        return $this->linkPackage($request, $sourceRecordPackage);
    }

    public function createFromSourceRecordPackage(Request $request, SourceRecordPackage $sourceRecordPackage)
    {
        return $this->createFromPackage($request, $sourceRecordPackage);
    }

    public function linkLegacyRecord(Request $request, LegacyRecord $legacyRecord)
    {
        $data = $request->validate([
            'landowner_id' => ['required', 'exists:landowners,id'],
        ]);

        $landowner = Landowner::findOrFail($data['landowner_id']);

        $legacyRecord->update([
            'landowner_id' => $landowner->id,
        ]);

        AuditLogger::record(
            'source_record_linked_to_landowner',
            null,
            $legacyRecord,
            [
                'source_record_id' => $legacyRecord->id,
                'landowner_id' => $landowner->id,
                'landowner_name' => $landowner->full_name,
                'scope_note' => 'Administrative source-to-landowner linkage only. No ownership transfer or registry mutation was performed.',
            ]
        );

        return back()->with('success', 'Source record linked to existing landowner record successfully.');
    }

    public function createFromLegacyRecord(Request $request, LegacyRecord $legacyRecord)
    {
        $data = $this->validateLandownerInput($request);

        DB::transaction(function () use ($legacyRecord, $data) {
            $landowner = Landowner::create($data);

            $legacyRecord->update([
                'landowner_id' => $landowner->id,
            ]);

            AuditLogger::record(
                'landowner_created_from_source_record',
                null,
                $landowner,
                [
                    'source_record_id' => $legacyRecord->id,
                    'record_type' => $legacyRecord->record_type,
                    'landowner_id' => $landowner->id,
                    'landowner_name' => $landowner->full_name,
                    'scope_note' => 'Created an administrative Landowner Record from source/provenance data only. This does not verify ownership, transfer land, or mutate registry records.',
                ]
            );

            AuditLogger::record(
                'source_record_linked_to_created_landowner',
                null,
                $legacyRecord,
                [
                    'source_record_id' => $legacyRecord->id,
                    'landowner_id' => $landowner->id,
                    'landowner_name' => $landowner->full_name,
                    'scope_note' => 'Administrative linkage only. No ownership transfer or registry mutation was performed.',
                ]
            );
        });

        return back()->with('success', 'Landowner record created from source record and linked successfully.');
    }

    private function validateLandownerInput(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'contact_number' => ['nullable', 'string', 'max:100'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
