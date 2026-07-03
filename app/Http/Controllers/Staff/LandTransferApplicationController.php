<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\ApplicationParcel;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use App\Models\LegacyRecord;
use App\Models\SourceRecordPackage;
use App\Models\Parcel;
use App\Services\AuditLogger;
use App\Services\LandholdingAreaValidationService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LandTransferApplicationController extends Controller
{
    public function show(LandTransferApplication $application)
    {
        $application->load([
            'documents.requiredDocument',
            'applicationParcels.parcel',
            'transferorLandowner',
            'transfereeLandowner',
            'clearance',
        ]);

        // 1) Required documents (checklist)
        $transferorRequirements = RequiredDocument::deduplicateForApplicationReview(
            RequiredDocument::where('applies_to', 'transferor')
                ->orderBy('blocks_acceptance', 'desc')
                ->orderBy('requirement_classification')
                ->orderBy('name')
                ->get()
        );

        $transfereeRequirements = RequiredDocument::deduplicateForApplicationReview(
            RequiredDocument::where('applies_to', 'transferee')
                ->orderBy('blocks_acceptance', 'desc')
                ->orderBy('requirement_classification')
                ->orderBy('name')
                ->get()
        );

        // 2) Uploaded docs for this application (keyed by required_document_id)
        $uploaded = ApplicationDocument::where('land_transfer_application_id', $application->id)
            ->get()
            ->keyBy('required_document_id');

        // 3) 5-hectare validation (assistive, centralized)
        $transfereeOwner = $application->transfereeLandowner;
        $fiveHectareValidation = app(LandholdingAreaValidationService::class)
            ->forApplication($application);
        $currentApprovedTotal = $fiveHectareValidation['current_active_total'];
        $pendingIncomingTotal = $fiveHectareValidation['pending_incoming_total'];
        $thisApplicationTotal = $fiveHectareValidation['this_application_total'];
        $projectedTotal = $fiveHectareValidation['projected_total'];
        $exceedsFiveHectares = $fiveHectareValidation['exceeds_limit'];

        $applicationTimeline = AuditLog::with('actor')
            ->where('land_transfer_application_id', $application->id)
            ->latest()
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
            'fiveHectareValidation',
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
public function create()
{
    $landowners = Landowner::query()
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

    $parcels = Parcel::query()
        ->orderBy('parcel_code')
        ->get();

    return view('staff.applications.create', compact(
        'landowners',
        'parcels'
    ));
}

public function store(Request $request)
{
    $validated = $request->validate([
        'transferor_landowner_id' => ['nullable', 'exists:landowners,id'],
        'transferee_landowner_id' => ['nullable', 'exists:landowners,id'],

        'applicant_name' => ['nullable', 'string', 'max:255'],
        'applicant_type' => ['nullable', 'string', 'in:transferor,transferee,authorized_representative,other'],
        'authorized_representative_name' => ['nullable', 'string', 'max:255'],
        'has_special_power_of_attorney' => ['nullable', 'boolean'],
        'or_number' => ['nullable', 'string', 'max:100'],
        'or_date' => ['nullable', 'date'],
        'amount_paid' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        'date_of_application' => ['nullable', 'date'],

        'transferor_name' => ['required', 'string', 'max:255'],
        'transferee_name' => ['required', 'string', 'max:255'],

        'municipality' => ['nullable', 'string', 'max:255'],
        'barangay' => ['nullable', 'string', 'max:255'],
        'date_filed' => ['nullable', 'date'],
        'date_of_transfer' => ['nullable', 'date'],
        'transfer_nature' => ['nullable', 'string', 'in:sale,donation,succession,extrajudicial_settlement,waiver_of_rights,other'],
        'is_succession_case' => ['nullable', 'boolean'],
        'retention_certificate_required' => ['nullable', 'boolean'],
        'retention_certificate_reference' => ['nullable', 'string', 'max:150'],
        'landholding_review_notes' => ['nullable', 'string', 'max:4000'],
        'remarks' => ['nullable', 'string'],

        'parcel_id' => ['nullable', 'exists:parcels,id'],
        'area_hectares' => ['nullable', 'numeric', 'min:0'],
    ]);

    $application = null;
    $hasSpecialPowerOfAttorney = $request->boolean('has_special_power_of_attorney');
    $isSuccessionCase = $request->boolean('is_succession_case') || (($validated['transfer_nature'] ?? null) === 'succession');
    $retentionCertificateRequired = $request->boolean('retention_certificate_required');

    DB::transaction(function () use ($validated, $hasSpecialPowerOfAttorney, $isSuccessionCase, $retentionCertificateRequired, &$application) {
        $applicantType = $validated['applicant_type'] ?? null;
        $applicantName = $validated['applicant_name'] ?? null;

        if (! filled($applicantName)) {
            $applicantName = match ($applicantType) {
                'transferee' => $validated['transferee_name'],
                default => $validated['transferor_name'],
            };
        }

        $applicationDate = $validated['date_of_application'] ?? $validated['date_filed'] ?? now()->toDateString();

        $application = LandTransferApplication::create([
            'application_code' => $this->generateApplicationCode(),
            'applicant_name' => $applicantName,
            'applicant_type' => $applicantType,
            'authorized_representative_name' => $validated['authorized_representative_name'] ?? null,
            'has_special_power_of_attorney' => $hasSpecialPowerOfAttorney,
            'or_number' => $validated['or_number'] ?? null,
            'or_date' => $validated['or_date'] ?? null,
            'amount_paid' => $validated['amount_paid'] ?? null,
            'date_of_application' => $applicationDate,
            'transferor_landowner_id' => $validated['transferor_landowner_id'] ?? null,
            'transferee_landowner_id' => $validated['transferee_landowner_id'] ?? null,
            'transferor_name' => $validated['transferor_name'],
            'transferee_name' => $validated['transferee_name'],
            'municipality' => $validated['municipality'] ?? null,
            'barangay' => $validated['barangay'] ?? null,
            'date_filed' => $validated['date_filed'] ?? $applicationDate,
            'date_of_transfer' => $validated['date_of_transfer'] ?? null,
            'transfer_nature' => $validated['transfer_nature'] ?? null,
            'is_succession_case' => $isSuccessionCase,
            'retention_certificate_required' => $retentionCertificateRequired,
            'retention_certificate_reference' => $retentionCertificateRequired
                ? ($validated['retention_certificate_reference'] ?? null)
                : null,
            'landholding_review_notes' => $validated['landholding_review_notes'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => Auth::id(),
        ]);

        if (! empty($validated['parcel_id'])) {
            $parcel = Parcel::findOrFail($validated['parcel_id']);

            $application->applicationParcels()->create([
                'parcel_id' => $parcel->id,
                'area_hectares' => $validated['area_hectares'] ?? $parcel->area_hectares,
                'area_square_meters' => $parcel->area_square_meters,
                'parcel_code' => $parcel->parcel_code,
                'title_no' => $parcel->title_no,
                'tax_decl_no' => $parcel->tax_decl_no,
                'lot_number' => $parcel->lot_number,
                'survey_plan_number' => $parcel->survey_plan_number,
                'title_type' => $parcel->title_type,
                'rod_office' => $parcel->rod_office,
            ]);
        }

        AuditLogger::record(
            'application_created',
            $application,
            $application,
            [
                'status' => $application->status,
                'applicant_name' => $application->applicant_name,
                'applicant_type' => $application->applicant_type,
                'or_number' => $application->or_number,
                'transfer_nature' => $application->transfer_nature,
                'is_succession_case' => $application->is_succession_case,
                'retention_certificate_required' => $application->retention_certificate_required,
                'retention_certificate_reference' => $application->retention_certificate_reference,
                'transferor_name' => $application->transferor_name,
                'transferee_name' => $application->transferee_name,
                'parcel_id' => $validated['parcel_id'] ?? null,
                'scope_note' => 'Application encoding only. No ownership transfer or registry mutation was performed.',
            ]
        );

        app(NotificationService::class)->notifyStaffApplicationEncoded($application);
    });

    return redirect()
        ->route('staff.applications.show', $application)
        ->with('success', 'Application encoded successfully and placed under Pending Review by Legal Officer.');
}

private function generateApplicationCode(): string
{
    $year = now()->format('Y');
    $prefix = "{$year}-";

    $nextNumber = LandTransferApplication::query()
        ->where('application_code', 'LIKE', $prefix . '%')
        ->count() + 1;

    do {
        $code = $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
        $nextNumber++;
    } while (LandTransferApplication::where('application_code', $code)->exists());

    return $code;
}
    public function updateForm4Review(Request $request, LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return back()->with('error', 'LTC Form No. 4 review details are locked after release or denial.');
        }

        $validated = $request->validate([
            'ltc_form4_subject_land_findings' => ['nullable', 'array'],
            'ltc_form4_subject_land_findings.*' => ['nullable', 'string', 'max:120'],
            'ltc_form4_recommendation_findings' => ['nullable', 'array'],
            'ltc_form4_recommendation_findings.*' => ['nullable', 'string', 'max:120'],
            'ltc_form4_recommendation_decision' => ['nullable', 'in:approval,denial'],
            'ltc_form4_other_findings' => ['nullable', 'string', 'max:2000'],
            'ltc_form4_certified_at' => ['nullable', 'date'],
            'ltc_form4_certifying_officer_name' => ['nullable', 'string', 'max:255'],
        ]);

        $application->forceFill([
            'ltc_form4_subject_land_findings' => array_values($validated['ltc_form4_subject_land_findings'] ?? []),
            'ltc_form4_recommendation_findings' => array_values($validated['ltc_form4_recommendation_findings'] ?? []),
            'ltc_form4_recommendation_decision' => $validated['ltc_form4_recommendation_decision'] ?? null,
            'ltc_form4_other_findings' => $validated['ltc_form4_other_findings'] ?? null,
            'ltc_form4_certified_at' => $validated['ltc_form4_certified_at'] ?? null,
            'ltc_form4_certifying_officer_name' => $validated['ltc_form4_certifying_officer_name'] ?? null,
        ])->save();

        AuditLogger::record(
            'ltc_form4_review_updated',
            $application,
            $application,
            [
                'recommendation_decision' => $application->ltc_form4_recommendation_decision,
                'subject_land_findings_count' => count((array) $application->ltc_form4_subject_land_findings),
                'recommendation_findings_count' => count((array) $application->ltc_form4_recommendation_findings),
            ],
            Auth::id()
        );

        return back()->with('success', 'LTC Form No. 4 attestation and recommendation details updated.');
    }

}