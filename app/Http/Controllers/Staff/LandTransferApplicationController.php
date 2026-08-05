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
        $fiveHectareValidation = app(LandholdingAreaValidationService::class)
            ->forApplication($application);
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

        $transferorNames = collect($application->partyRows('transferor'))
            ->pluck('name')
            ->filter()
            ->unique()
            ->values();
        $transfereeNames = collect($application->partyRows('transferee'))
            ->pluck('name')
            ->filter()
            ->unique()
            ->values();

        $hasPriorRecordSignals =
            $parcelIds->isNotEmpty() ||
            $parcelCodes->isNotEmpty() ||
            $titleNumbers->isNotEmpty() ||
            $transferorNames->isNotEmpty() ||
            $transfereeNames->isNotEmpty();

        $matchedSourceRecords = collect();
        $matchedSourcePackages = collect();

        if ($hasPriorRecordSignals) {
            $matchedSourceRecords = LegacyRecord::query()
                ->with(['parcel', 'package'])
                ->where(function ($query) use (
                    $parcelIds,
                    $parcelCodes,
                    $titleNumbers,
                    $transferorNames,
                    $transfereeNames
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

                    foreach ($transferorNames as $transferorName) {
                        $query->orWhere('landowner_name', 'ILIKE', '%' . $transferorName . '%')
                            ->orWhere('transferor_name', 'ILIKE', '%' . $transferorName . '%');
                    }

                    foreach ($transfereeNames as $transfereeName) {
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
                    $transferorNames,
                    $transfereeNames
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

                    foreach ($transferorNames as $transferorName) {
                        $query->orWhere('landowner_name', 'ILIKE', '%' . $transferorName . '%')
                            ->orWhere('transferor_name', 'ILIKE', '%' . $transferorName . '%');
                    }

                    foreach ($transfereeNames as $transfereeName) {
                        $query->orWhere('transferee_name', 'ILIKE', '%' . $transfereeName . '%');
                    }
                })
                ->latest()
                ->limit(10)
                ->get();
        }

        $parcelOptions = Parcel::query()
            ->orderBy('parcel_code')
            ->get();

        $linkedLandownerIds = $application->linkedLandownerIds();
        $landowners = Landowner::query()
            ->whereIn('id', $linkedLandownerIds)
            ->get()
            ->concat(
                Landowner::query()
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->limit(500)
                    ->get()
            )
            ->unique('id')
            ->sortBy(fn (Landowner $landowner) => mb_strtolower($landowner->last_name . ' ' . $landowner->first_name))
            ->values();

        return view('staff.applications.show', compact(
            'application',
            'transferorRequirements',
            'transfereeRequirements',
            'uploaded',
            'exceedsFiveHectares',
            'fiveHectareValidation',
            'applicationTimeline',
            'matchedSourceRecords',
            'matchedSourcePackages',
            'parcelOptions',
            'landowners',
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

    $locationOptions = config('dar_locations.municipalities', []);

    return view('staff.applications.create', compact(
        'landowners',
        'parcels',
        'locationOptions'
    ));
}

public function store(Request $request)
{
    $validated = $request->validate([
        'transferor_landowner_id' => ['nullable', 'exists:landowners,id'],
        'transferee_landowner_id' => ['nullable', 'exists:landowners,id'],
        'transferors' => ['nullable', 'array'],
        'transferors.*.landowner_id' => ['nullable', 'exists:landowners,id'],
        'transferors.*.name' => ['nullable', 'string', 'max:255'],
        'transferees' => ['nullable', 'array'],
        'transferees.*.landowner_id' => ['nullable', 'exists:landowners,id'],
        'transferees.*.name' => ['nullable', 'string', 'max:255'],

        'applicant_name' => ['nullable', 'string', 'max:255'],
        'applicant_type' => ['nullable', 'string', 'in:transferor,transferee,authorized_representative,other'],
        'authorized_representative_name' => ['nullable', 'string', 'max:255'],
        'has_special_power_of_attorney' => ['nullable', 'boolean'],
        'or_number' => ['nullable', 'string', 'max:100'],
        'or_date' => ['nullable', 'date'],
        'amount_paid' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        'date_of_application' => ['nullable', 'date'],

        'transferor_name' => ['nullable', 'string', 'max:1000'],
        'transferee_name' => ['nullable', 'string', 'max:1000'],

        'municipality' => ['nullable', 'string', 'max:255'],
        'barangay' => ['nullable', 'string', 'max:255'],
        'date_filed' => ['nullable', 'date'],
        'date_of_clearance_release' => ['nullable', 'date'],
        'transfer_nature' => ['nullable', 'string', 'max:255'],
        'transfer_instruments' => ['nullable', 'array'],
        'transfer_instruments.*.name' => ['nullable', 'string', 'max:255'],
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
    $isSuccessionCase = $request->boolean('is_succession_case') || str_contains(strtolower(collect($validated['transfer_instruments'] ?? [])->pluck('name')->implode(' ')), 'succession');
    $retentionCertificateRequired = $request->boolean('retention_certificate_required');
    $transferors = $this->normalizePartyRows($validated['transferors'] ?? [], $validated['transferor_name'] ?? null, $validated['transferor_landowner_id'] ?? null);
    $transferees = $this->normalizePartyRows($validated['transferees'] ?? [], $validated['transferee_name'] ?? null, $validated['transferee_landowner_id'] ?? null);
    $transferInstruments = $this->normalizeInstrumentRows($validated['transfer_instruments'] ?? [], $validated['transfer_nature'] ?? null);

    if (empty($transferors)) {
        return back()->withInput()->withErrors(['transferors.0.name' => 'At least one transferor is required.']);
    }
    if (empty($transferees)) {
        return back()->withInput()->withErrors(['transferees.0.name' => 'At least one transferee is required.']);
    }

    $transferorSummary = collect($transferors)->pluck('name')->filter()->implode('; ');
    $transfereeSummary = collect($transferees)->pluck('name')->filter()->implode('; ');

    DB::transaction(function () use ($validated, $hasSpecialPowerOfAttorney, $isSuccessionCase, $retentionCertificateRequired, $transferors, $transferees, $transferInstruments, $transferorSummary, $transfereeSummary, &$application) {
        $applicantType = $validated['applicant_type'] ?? null;
        $applicantName = $validated['applicant_name'] ?? null;

        if (! filled($applicantName)) {
            $applicantName = match ($applicantType) {
                'transferee' => $transfereeSummary,
                default => $transferorSummary,
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
            'transferor_landowner_id' => $transferors[0]['landowner_id'] ?? ($validated['transferor_landowner_id'] ?? null),
            'transferee_landowner_id' => $transferees[0]['landowner_id'] ?? ($validated['transferee_landowner_id'] ?? null),
            'transferor_name' => $transferorSummary,
            'transferors' => $transferors,
            'transferee_name' => $transfereeSummary,
            'transferees' => $transferees,
            'municipality' => $validated['municipality'] ?? null,
            'barangay' => $validated['barangay'] ?? null,
            'date_filed' => $validated['date_filed'] ?? $applicationDate,
            'date_of_transfer' => null,
            'date_of_clearance_release' => $validated['date_of_clearance_release'] ?? null,
            'ltc_page_number' => 1,
            'transfer_nature' => $validated['transfer_nature'] ?? null,
            'transfer_instruments' => $transferInstruments,
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
                'transfer_instruments' => $application->transfer_instruments,
                'transfer_nature' => $application->transfer_nature,
                'is_succession_case' => $application->is_succession_case,
                'retention_certificate_required' => $application->retention_certificate_required,
                'retention_certificate_reference' => $application->retention_certificate_reference,
                'transferor_name' => $application->transferor_name,
                'transferors' => $application->transferors,
                'transferee_name' => $application->transferee_name,
                'transferees' => $application->transferees,
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


    public function storeParcel(Request $request, LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return back()->with('error', 'Linked parcel records are locked after final decision.');
        }

        $validated = $request->validate([
            'parcel_id' => ['required', 'exists:parcels,id'],
            'area_hectares' => ['nullable', 'numeric', 'min:0.0001'],
        ]);

        $parcel = Parcel::findOrFail($validated['parcel_id']);
        $areaHectares = $validated['area_hectares'] ?? $parcel->area_hectares;
        $areaSquareMeters = $areaHectares !== null
            ? round(((float) $areaHectares) * 10000, 2)
            : $parcel->area_square_meters;

        $applicationParcel = $application->applicationParcels()
            ->where('parcel_id', $parcel->id)
            ->first();

        $payload = [
            'parcel_id' => $parcel->id,
            'area_hectares' => $areaHectares,
            'area_square_meters' => $areaSquareMeters,
            'parcel_code' => $parcel->parcel_code,
            'title_no' => $parcel->title_no,
            'tax_decl_no' => $parcel->tax_decl_no,
            'lot_number' => $parcel->lot_number,
            'survey_plan_number' => $parcel->survey_plan_number,
            'title_type' => $parcel->title_type,
            'rod_office' => $parcel->rod_office,
        ];

        if ($applicationParcel) {
            $applicationParcel->update($payload);
            $action = 'application_parcel_updated';
            $message = 'Linked parcel reference updated.';
        } else {
            $applicationParcel = $application->applicationParcels()->create($payload);
            $action = 'application_parcel_added';
            $message = 'Parcel reference added to the application review.';
        }

        AuditLogger::record(
            $action,
            $application,
            $application,
            [
                'application_parcel_id' => $applicationParcel->id,
                'parcel_id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code,
                'area_hectares' => $areaHectares,
            ],
            Auth::id()
        );

        return back()->with('success', $message);
    }

    public function destroyParcel(LandTransferApplication $application, ApplicationParcel $applicationParcel)
    {
        if ($application->isFinalized()) {
            return back()->with('error', 'Linked parcel records are locked after final decision.');
        }

        if ((int) $applicationParcel->land_transfer_application_id !== (int) $application->id) {
            abort(404);
        }

        $auditPayload = [
            'application_parcel_id' => $applicationParcel->id,
            'parcel_id' => $applicationParcel->parcel_id,
            'parcel_code' => $applicationParcel->parcel_code,
            'area_hectares' => $applicationParcel->area_hectares,
        ];

        $applicationParcel->delete();

        AuditLogger::record(
            'application_parcel_removed',
            $application,
            $application,
            $auditPayload,
            Auth::id()
        );

        return back()->with('success', 'Linked parcel reference removed from the application review.');
    }


private function normalizePartyRows(array $rows, ?string $legacyName = null, $legacyLandownerId = null): array
{
    $normalized = collect($rows)->map(function ($row) {
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') return null;

        $parcelShares = collect((array) ($row['parcel_shares'] ?? []))
            ->mapWithKeys(function ($value, $key) {
                if ($value === null || $value === '') {
                    return [];
                }

                return [(string) $key => round((float) $value, 4)];
            })
            ->all();

        return [
            'landowner_id' => filled($row['landowner_id'] ?? null) ? (int) $row['landowner_id'] : null,
            'name' => $name,
            'parcel_shares' => $parcelShares,
        ];
    })->filter()->values()->all();

    if (empty($normalized) && filled($legacyName)) {
        $normalized[] = [
            'landowner_id' => filled($legacyLandownerId) ? (int) $legacyLandownerId : null,
            'name' => trim((string) $legacyName),
            'parcel_shares' => [],
        ];
    }

    return $normalized;
}

private function normalizeInstrumentRows(array $rows, ?string $primaryInstrument = null): array
{
    $normalized = collect($rows)
        ->map(fn ($row) => trim((string) ($row['name'] ?? '')))
        ->filter()
        ->unique()
        ->map(fn ($name) => ['name' => $name])
        ->values()
        ->all();

    if (empty($normalized) && filled($primaryInstrument)) {
        $normalized[] = ['name' => LandTransferApplication::transferNatureOptions()[$primaryInstrument] ?? $primaryInstrument];
    }

    return $normalized;
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