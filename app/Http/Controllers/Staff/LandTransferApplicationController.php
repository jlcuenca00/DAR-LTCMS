<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\ApplicationParcel;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use App\Models\LegacyRecord;
use App\Models\SourceRecordPackage;

class LandTransferApplicationController extends Controller
{
    public function show(LandTransferApplication $application)
    {
        $application->load([
            'documents',
            'applicationParcels.parcel',
            'transferorLandowner',
            'transfereeLandowner',
            'clearance',
        ]);

        // 1) Required documents (checklist)
        $transferorRequirements = RequiredDocument::where('applies_to', 'transferor')
            ->orderBy('is_mandatory', 'desc')
            ->orderBy('name')
            ->get();

        $transfereeRequirements = RequiredDocument::where('applies_to', 'transferee')
            ->orderBy('is_mandatory', 'desc')
            ->orderBy('name')
            ->get();

        // 2) Uploaded docs for this application (keyed by required_document_id)
        $uploaded = ApplicationDocument::where('land_transfer_application_id', $application->id)
            ->get()
            ->keyBy('required_document_id');

        // 3) 5-hectare validation (assistive)
        $transfereeOwner = null;
        $currentApprovedTotal = 0;
        $pendingIncomingTotal = 0;
        $thisApplicationTotal = 0;
        $projectedTotal = 0;
        $exceedsFiveHectares = false;

        if ($application->transferee_landowner_id) {
            $transfereeOwner = Landowner::find($application->transferee_landowner_id);

            // (1) Approved/active landholdings
            $currentApprovedTotal = Landholding::where('landowner_id', $transfereeOwner->id)
                ->where('status', 'active')
                ->sum('area_hectares');

            // (2) Pending incoming from OTHER applications (draft/pending_review)
            $pendingIncomingTotal = ApplicationParcel::whereHas('application', function ($q) use ($transfereeOwner, $application) {
                    $q->where('transferee_landowner_id', $transfereeOwner->id)
                      ->where('id', '!=', $application->id)
                      ->whereIn('status', ['draft', 'pending_review']);
                })
                ->sum('area_hectares');

            // (3) Current application parcels total
            $thisApplicationTotal = ApplicationParcel::where('land_transfer_application_id', $application->id)
                ->sum('area_hectares');

            $projectedTotal = (float) $currentApprovedTotal
                            + (float) $pendingIncomingTotal
                            + (float) $thisApplicationTotal;

            $exceedsFiveHectares = $projectedTotal > 5.0000;
        }

        $applicationTimeline = AuditLog::with('actor')
            ->where('land_transfer_application_id', $application->id)
            ->oldest()
            ->get();

                    $applicationParcels = $application->applicationParcels
            ->pluck('parcel')
            ->filter();

        $parcelIds = $applicationParcels
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();

        $parcelCodes = $applicationParcels
            ->pluck('parcel_code')
            ->filter()
            ->unique()
            ->values();

        $titleNumbers = $applicationParcels
            ->pluck('title_no')
            ->filter()
            ->unique()
            ->values();

        $transferorName = $application->transferorLandowner?->full_name;
        $transfereeName = $application->transfereeLandowner?->full_name;

        $hasPriorRecordSignals =
            $parcelIds->isNotEmpty() ||
            $parcelCodes->isNotEmpty() ||
            $titleNumbers->isNotEmpty() ||
            filled($transferorName) ||
            filled($transfereeName);

        $matchedSourceRecords = collect();
        $matchedSourcePackages = collect();

        if ($hasPriorRecordSignals) {
            $matchedSourceRecords = LegacyRecord::query()
                ->with(['parcel', 'package'])
                ->where(function ($query) use (
                    $parcelIds,
                    $parcelCodes,
                    $titleNumbers,
                    $transferorName,
                    $transfereeName
                ) {
                    if ($parcelIds->isNotEmpty()) {
                        $query->orWhereIn('parcel_id', $parcelIds);
                    }

                    if ($parcelCodes->isNotEmpty()) {
                        $query->orWhereIn('parcel_code', $parcelCodes);
                    }

                    if ($titleNumbers->isNotEmpty()) {
                        $query->orWhereIn('title_number', $titleNumbers);
                    }

                    if (filled($transferorName)) {
                        $query->orWhere('landowner_name', 'ILIKE', '%' . $transferorName . '%')
                            ->orWhere('transferor_name', 'ILIKE', '%' . $transferorName . '%');
                    }

                    if (filled($transfereeName)) {
                        $query->orWhere('transferee_name', 'ILIKE', '%' . $transfereeName . '%');
                    }
                })
                ->latest()
                ->limit(25)
                ->get();

            $matchedSourcePackages = SourceRecordPackage::query()
                ->with(['parcel', 'records'])
                ->where(function ($query) use (
                    $parcelIds,
                    $parcelCodes,
                    $titleNumbers,
                    $transferorName,
                    $transfereeName
                ) {
                    if ($parcelIds->isNotEmpty()) {
                        $query->orWhereIn('parcel_id', $parcelIds);
                    }

                    if ($parcelCodes->isNotEmpty()) {
                        $query->orWhereIn('parcel_code', $parcelCodes);
                    }

                    if ($titleNumbers->isNotEmpty()) {
                        $query->orWhereIn('title_number', $titleNumbers);
                    }

                    if (filled($transferorName)) {
                        $query->orWhere('landowner_name', 'ILIKE', '%' . $transferorName . '%')
                            ->orWhere('transferor_name', 'ILIKE', '%' . $transferorName . '%');
                    }

                    if (filled($transfereeName)) {
                        $query->orWhere('transferee_name', 'ILIKE', '%' . $transfereeName . '%');
                    }
                })
                ->latest()
                ->limit(10)
                ->get();
        }

        return view('staff.applications.show', compact(
            'application',
            'transferorRequirements',
            'transfereeRequirements',
            'uploaded',
            'transfereeOwner',
            'currentApprovedTotal',
            'pendingIncomingTotal',
            'thisApplicationTotal',
            'projectedTotal',
            'exceedsFiveHectares',
            'applicationTimeline',
            'matchedSourceRecords',
            'matchedSourcePackages',
        ));
    }
    public function index(Request $request)
{
    $filters = $request->validate([
        'search' => ['nullable', 'string', 'max:255'],
        'status' => ['nullable', 'string', 'max:50'],
        'municipality' => ['nullable', 'string', 'max:255'],
        'barangay' => ['nullable', 'string', 'max:255'],
        'document_reference_number' => ['nullable', 'string', 'max:150'],
    ]);

    $applicationsQuery = LandTransferApplication::query()
        ->latest();

    if (! empty($filters['search'])) {
        $search = strtolower($filters['search']);

        $applicationsQuery->where(function ($query) use ($search) {
            $query->whereRaw('LOWER(application_code) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(transferor_name) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(transferee_name) LIKE ?', ["%{$search}%"]);
        });
    }

    if (! empty($filters['status'])) {
        $applicationsQuery->where('status', $filters['status']);
    }

    if (! empty($filters['municipality'])) {
        $applicationsQuery->where('municipality', $filters['municipality']);
    }

    if (! empty($filters['barangay'])) {
        $applicationsQuery->where('barangay', $filters['barangay']);
    }

    if (! empty($filters['document_reference_number'])) {
        $documentReferenceNumber = strtolower($filters['document_reference_number']);

        $applicationsQuery->whereIn('id', function ($query) use ($documentReferenceNumber) {
            $query->select('land_transfer_application_id')
                ->from('application_documents')
                ->whereRaw('LOWER(document_reference_number) LIKE ?', ["%{$documentReferenceNumber}%"]);
        });
    }

    $applications = $applicationsQuery
        ->paginate(15)
        ->withQueryString();

    $statuses = LandTransferApplication::query()
        ->select('status')
        ->distinct()
        ->orderBy('status')
        ->pluck('status');

    $municipalities = LandTransferApplication::query()
        ->whereNotNull('municipality')
        ->select('municipality')
        ->distinct()
        ->orderBy('municipality')
        ->pluck('municipality');

    $barangays = LandTransferApplication::query()
        ->whereNotNull('barangay')
        ->when(! empty($filters['municipality']), function ($query) use ($filters) {
            $query->where('municipality', $filters['municipality']);
        })
        ->select('barangay')
        ->distinct()
        ->orderBy('barangay')
        ->pluck('barangay');

    return view('staff.applications.index', compact(
        'applications',
        'filters',
        'statuses',
        'municipalities',
        'barangays'
    ));
}
}