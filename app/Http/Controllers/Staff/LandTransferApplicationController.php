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
use App\Services\ApplicationWorkflowDependencyService;
use App\Services\LandholdingAreaValidationService;
use App\Services\LandownerConcurrencyService;
use App\Services\NotificationService;
use App\Services\ParcelConcurrencyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'decisionRecordedBy',
            'returnedForComplianceBy',
            'activeComplianceNotice.requestedBy',
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
        $workflowDependencyFingerprint = app(ApplicationWorkflowDependencyService::class)
            ->fingerprint($application);

        $timelineQuery = AuditLog::with('actor')
            ->where('land_transfer_application_id', $application->id);

        $latestApplicationActivity = (clone $timelineQuery)
            ->latest()
            ->first();

        $applicationTimeline = $timelineQuery
            ->latest()
            ->paginate(20, ['*'], 'timeline_page')
            ->withQueryString();

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

        $linkedLandownerIds = $application->linkedLandownerIds();
        $landowners = Landowner::query()
            ->whereIn('id', $linkedLandownerIds)
            ->get()
            ->keyBy('id');

        $partyLinkDependencyFingerprint = app(\App\Services\ApplicationPartyLinkReviewService::class)->fingerprint($application);

        return view('staff.applications.show', compact(
            'application',
            'transferorRequirements',
            'transfereeRequirements',
            'uploaded',
            'exceedsFiveHectares',
            'fiveHectareValidation',
            'workflowDependencyFingerprint',
            'partyLinkDependencyFingerprint',
            'applicationTimeline',
            'latestApplicationActivity',
            'matchedSourceRecords',
            'matchedSourcePackages',
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
        $search = mb_strtolower($filters['search']);

        $applicationsQuery->whereRaw(
            "LOWER(COALESCE(application_code, '') || ' ' || COALESCE(transferor_name, '') || ' ' || COALESCE(transferee_name, '')) LIKE ?",
            ["%{$search}%"]
        );
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
public function create(Request $request)
{
    $oldTransferors = collect((array) $request->session()->getOldInput('transferors', []));
    $oldTransferees = collect((array) $request->session()->getOldInput('transferees', []));

    $selectedLandownerIds = $oldTransferors
        ->merge($oldTransferees)
        ->pluck('landowner_id')
        ->push($request->session()->getOldInput('transferor_landowner_id'))
        ->push($request->session()->getOldInput('transferee_landowner_id'))
        ->filter()
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values();

    $selectedLandowners = Landowner::query()
        ->whereIn('id', $selectedLandownerIds)
        ->get()
        ->keyBy('id');

    $selectedParcelId = $request->session()->getOldInput('parcel_id');
    $selectedParcel = $selectedParcelId
        ? Parcel::query()->where('status', 'active')->find($selectedParcelId)
        : null;

    $locationOptions = config('dar_locations.municipalities', []);

    return view('staff.applications.create', compact(
        'selectedLandowners',
        'selectedParcel',
        'locationOptions'
    ));
}

public function store(Request $request)
{
    $validated = $request->validate([
        'transferor_landowner_id' => ['nullable', 'exists:landowners,id'],
        'transferee_landowner_id' => ['nullable', 'exists:landowners,id'],
        'transferors' => ['nullable', 'array'],
        'transferors.*' => ['array:name,landowner_id'],
        'transferors.*.landowner_id' => ['nullable', 'distinct', 'exists:landowners,id'],
        'transferors.*.name' => ['nullable', 'string', 'max:255'],
        'transferees' => ['nullable', 'array'],
        'transferees.*' => ['array:name,landowner_id'],
        'transferees.*.landowner_id' => ['nullable', 'distinct', 'exists:landowners,id'],
        'transferees.*.name' => ['nullable', 'string', 'max:255'],

        'applicant_name' => ['nullable', 'string', 'max:255'],
        'applicant_type' => ['nullable', 'string', 'in:transferor,transferee,authorized_representative,other'],
        'authorized_representative_name' => ['nullable', 'string', 'max:255'],
        'has_special_power_of_attorney' => ['nullable', 'boolean'],
        'date_of_application' => ['nullable', 'date', 'before_or_equal:today'],

        'transferor_name' => ['nullable', 'string', 'max:1000'],
        'transferee_name' => ['nullable', 'string', 'max:1000'],

        'municipality' => ['nullable', 'string', 'max:255'],
        'barangay' => ['nullable', 'string', 'max:255'],
        'date_filed' => ['nullable', 'date', 'before_or_equal:today'],
        'transfer_nature' => ['nullable', 'string', 'max:255'],
        'transfer_instruments' => ['nullable', 'array'],
        'transfer_instruments.*.name' => ['nullable', 'string', 'max:255'],
        'is_succession_case' => ['nullable', 'boolean'],
        'retention_certificate_required' => ['nullable', 'boolean'],
        'retention_certificate_reference' => ['nullable', 'string', 'max:150'],
        'landholding_review_notes' => ['nullable', 'string', 'max:4000'],
        'remarks' => ['nullable', 'string'],

        'parcel_id' => ['nullable', 'exists:parcels,id'],
        'area_hectares' => ['nullable', 'numeric', 'min:0.0001'],
    ]);

    $application = null;
    $hasSpecialPowerOfAttorney = $request->boolean('has_special_power_of_attorney');
    $retentionCertificateRequired = $request->boolean('retention_certificate_required');
    $transferors = $this->normalizePartyRows($validated['transferors'] ?? [], $validated['transferor_name'] ?? null, $validated['transferor_landowner_id'] ?? null);
    $transferees = $this->normalizePartyRows($validated['transferees'] ?? [], $validated['transferee_name'] ?? null, $validated['transferee_landowner_id'] ?? null);
    $transferInstruments = $this->normalizeInstrumentRows($validated['transfer_instruments'] ?? [], $validated['transfer_nature'] ?? null);
    $normalizedInstrumentText = mb_strtolower(collect($transferInstruments)->pluck('name')->implode(' '));
    $isSuccessionCase = $request->boolean('is_succession_case')
        || ($validated['transfer_nature'] ?? null) === 'succession'
        || str_contains($normalizedInstrumentText, 'succession')
        || str_contains($normalizedInstrumentText, 'inheritance');

    if (empty($transferors)) {
        return back()->withInput()->withErrors(['transferors.0.name' => 'At least one transferor is required.']);
    }
    if (empty($transferees)) {
        return back()->withInput()->withErrors(['transferees.0.name' => 'At least one transferee is required.']);
    }

    $transferorSummary = collect($transferors)->pluck('name')->filter()->implode('; ');
    $transfereeSummary = collect($transferees)->pluck('name')->filter()->implode('; ');

    DB::transaction(function () use ($validated, $hasSpecialPowerOfAttorney, $isSuccessionCase, $retentionCertificateRequired, $transferors, $transferees, $transferInstruments, $transferorSummary, $transfereeSummary, &$application) {
        app(LandownerConcurrencyService::class)->lockLandowners(
            collect($transferees)->pluck('landowner_id')->filter()->all()
        );

        $applicantType = $validated['applicant_type'] ?? null;
        $applicantName = $validated['applicant_name'] ?? null;

        if (! filled($applicantName)) {
            $applicantName = match ($applicantType) {
                'transferee' => $transfereeSummary,
                'authorized_representative' => filled($validated['authorized_representative_name'] ?? null)
                    ? trim((string) $validated['authorized_representative_name'])
                    : null,
                'other' => null,
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
            $parcel = app(ParcelConcurrencyService::class)
                ->lockParcel((int) $validated['parcel_id']);

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
        ->with('success', 'Application encoded successfully and placed under Legal Completeness Review.');
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

        return DB::transaction(function () use ($validated, $application) {
            app(LandownerConcurrencyService::class)->lockLandowners(
                $application->linkedLandownerIds('transferee')->all()
            );

            $parcel = app(ParcelConcurrencyService::class)
                ->lockParcel((int) $validated['parcel_id']);

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
        });
    }

    public function destroyParcel(LandTransferApplication $application, ApplicationParcel $applicationParcel)
    {
        if ($application->isFinalized()) {
            return back()->with('error', 'Linked parcel records are locked after final decision.');
        }

        if ((int) $applicationParcel->land_transfer_application_id !== (int) $application->id) {
            abort(404);
        }

        return DB::transaction(function () use ($application, $applicationParcel) {
            app(LandownerConcurrencyService::class)->lockLandowners(
                $application->linkedLandownerIds('transferee')->all()
            );

            if ($applicationParcel->parcel_id) {
                app(ParcelConcurrencyService::class)->lockParcel((int) $applicationParcel->parcel_id);
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
        });
    }


private function normalizePartyRows(array $rows, ?string $legacyName = null, $legacyLandownerId = null): array
{
    $normalized = collect($rows)->map(function ($row) {
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') return null;

        return [];
                }

                return [(string) $key => round((float) $value, 4)];
            })
            ->all();

        return [
            'landowner_id' => filled($row['landowner_id'] ?? null) ? (int) $row['landowner_id'] : null,
            'name' => $name,
            'parcel_shares' => [],
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
    $driver = DB::connection()->getDriverName();

    /*
     * Application codes are allocated inside the surrounding creation
     * transaction. PostgreSQL uses a year-scoped advisory transaction lock so
     * different annual sequences do not require locking the entire table.
     */
    if ($driver === 'pgsql') {
        DB::selectOne(
            'SELECT pg_advisory_xact_lock(hashtextextended(?, 0))',
            ['dar-ltcms:application-code:' . $year]
        );

        $row = DB::selectOne(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(application_code FROM '[0-9]+$') AS INTEGER)), 0) AS max_sequence
             FROM land_transfer_applications
             WHERE application_code LIKE ?
               AND application_code ~ ?",
            [$prefix . '%', '^' . preg_quote($year, '/') . '-[0-9]+$']
        );

        $nextNumber = ((int) ($row->max_sequence ?? 0)) + 1;

        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /*
     * Non-PostgreSQL is used only by lightweight development/test setups.
     * Preserve compatible behavior there without PostgreSQL-specific SQL.
     */
    $existingSequences = LandTransferApplication::query()
        ->where('application_code', 'LIKE', $prefix . '%')
        ->pluck('application_code')
        ->map(function ($code) use ($year): ?int {
            $pattern = '/^' . preg_quote($year, '/') . '-(\\d+)$/';

            return preg_match($pattern, (string) $code, $matches)
                ? (int) $matches[1]
                : null;
        })
        ->filter(fn ($sequence) => $sequence !== null);

    $nextNumber = max(1, ((int) $existingSequences->max()) + 1);

    do {
        $code = $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
        $nextNumber++;
    } while (LandTransferApplication::where('application_code', $code)->exists());

    return $code;
}
    public function updateForm4Review(Request $request, LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return back()->with('error', 'LTC Form No. 4 review details are locked after a final clearance decision.');
        }

        $validated = $request->validate([
            'ltc_form4_subject_land_findings' => ['nullable', 'array', 'max:'.count(LandTransferApplication::form4SubjectLandOptions())],
            'ltc_form4_subject_land_findings.*' => [
                'required',
                'string',
                'distinct',
                Rule::in(array_keys(LandTransferApplication::form4SubjectLandOptions())),
            ],
            'ltc_form4_recommendation_findings' => ['nullable', 'array', 'max:'.count(LandTransferApplication::form4RecommendationOptions())],
            'ltc_form4_recommendation_findings.*' => [
                'required',
                'string',
                'distinct',
                Rule::in(array_keys(LandTransferApplication::form4RecommendationOptions())),
            ],
            'ltc_form4_recommendation_decision' => ['nullable', 'in:approval,denial'],
            'ltc_form4_other_findings' => ['nullable', 'string', 'max:2000'],
            'ltc_form4_certified_at' => ['nullable', 'date', 'before_or_equal:today'],
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