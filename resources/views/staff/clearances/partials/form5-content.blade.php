@php
    $rawDecisionStatus = strtolower((string) $clearance->decision_status);
    $isGranted = in_array($rawDecisionStatus, ['released', 'approved'], true);
    $decisionLabel = $isGranted ? 'GRANTED' : 'DENIED';

    $generatedAt = $clearance->generated_at;
    $reviewedAt = $clearance->reviewed_at;
    $issueDate = $application->date_of_clearance_release ?? $generatedAt ?? $reviewedAt ?? now();

    $parcels = collect($clearance->parcel_snapshot ?? []);
    $firstParcel = $parcels->first() ?? [];

    $titleType = $firstParcel['title_type'] ?? 'TCT';
    $titleNo = $firstParcel['title_number'] ?? $firstParcel['title_no'] ?? '__________';
    $taxDeclNo = $firstParcel['tax_decl_no'] ?? '__________';
    $lotNo = $firstParcel['lot_number'] ?? '__________';
    $surveyNo = $firstParcel['survey_plan_number'] ?? '__________';
    $areaSqm = $firstParcel['area_square_meters'] ?? null;
    if (! $areaSqm && filled($firstParcel['area_hectares'] ?? null)) {
        $areaSqm = round(((float) $firstParcel['area_hectares']) * 10000, 2);
    }
    if (! $areaSqm) {
        $areaSqm = round(((float) $clearance->total_area_hectares) * 10000, 2);
    }
    $areaText = $areaSqm > 0 ? rtrim(rtrim(number_format((float) $areaSqm, 2, '.', ''), '0'), '.') . ' sq. m.' : '__________ sq. m.';

    $location = trim(($clearance->barangay ? $clearance->barangay . ', ' : '') . ($clearance->municipality ?? ''));
    $location = $location !== '' ? $location . ', Negros Oriental' : '__________';

    $metadataItems = collect($application->documents ?? [])
        ->map(fn ($document) => $document->document_metadata ?? [])
        ->filter(fn ($metadata) => is_array($metadata) && ! empty($metadata));

    $metaFirst = function (array $keys) use ($metadataItems) {
        foreach ($metadataItems as $metadata) {
            foreach ($keys as $key) {
                $value = data_get($metadata, $key);
                if (filled($value)) {
                    return is_array($value) ? implode('; ', array_filter($value)) : (string) $value;
                }
            }
        }
        return null;
    };

    $ownerName = $metaFirst(['title_owner_names', 'document_owner_names']) ?: ($clearance->transferor_name ?: $application->transferorDisplayName());
    $subjectOf = $metaFirst(['transfer_document_title']) ?: $application->transferInstrumentDisplay();
    $subjectDate = $metaFirst(['notarization_date', 'date_issued']);
    $subjectLine = $subjectOf ?: '__________';
    if ($subjectDate) {
        try {
            $subjectLine .= ' dated ' . \Illuminate\Support\Carbon::parse($subjectDate)->format('m/d/Y');
        } catch (\Throwable $e) {
            $subjectLine .= ' dated ' . $subjectDate;
        }
    }

    $docNo = $metaFirst(['notarial_document_number']);
    $pageNo = $metaFirst(['notarial_page_number']);
    $bookNo = $metaFirst(['notarial_book_number']);
    $series = $metaFirst(['notarial_series']);
    $notary = $metaFirst(['notary_public']);

    $notarialLineParts = [];
    if ($docNo) $notarialLineParts[] = 'Doc No. ' . $docNo;
    if ($pageNo) $notarialLineParts[] = 'Page No. ' . $pageNo;
    if ($bookNo) $notarialLineParts[] = 'Book No. ' . $bookNo;
    if ($series) $notarialLineParts[] = 'Series of ' . $series;
    $notarialLine = count($notarialLineParts) ? implode(', ', $notarialLineParts) : 'Doc No. __, Page No. __, Book No. __, Series of ____';

    $transferorNames = $clearance->transferor_name ?: $application->transferorDisplayName();
    $transfereeNames = $clearance->transferee_name ?: $application->transfereeDisplayName();

    $showToolbar = $showToolbar ?? false;
    $returnRoute = $returnRoute ?? route('staff.applications.show', $application);
    $returnLabel = $returnLabel ?? 'Back to Application';

    $logoAsset = function (array $filenames) {
        foreach ($filenames as $filename) {
            foreach ([$filename, 'images/' . $filename, 'logos/' . $filename, 'clearance/' . $filename, 'clearances/' . $filename] as $storagePath) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($storagePath)) {
                    return asset('storage/' . $storagePath);
                }
            }
        }

        foreach ($filenames as $filename) {
            $path = public_path('images/' . $filename);
            if (is_file($path)) {
                return asset('images/' . $filename);
            }
        }

        return null;
    };

    $darLogo = $logoAsset(['dar-logo.png', 'dar-logo.jpg', 'dar-logo.svg', 'dar_logo.png', 'DAR-logo.png']);
    $bagongLogo = $logoAsset(['bagong-pilipinas.png', 'bagong-pilipinas.jpg', 'bagong-pilipinas.svg', 'bagong-pilipinas-logo.svg', 'bagong_pilipinas.png']);
@endphp

<style>
    @page { size: A4; margin: 8mm 11mm; }

    @font-face {
        font-family: 'LTC Gilroy';
        src: local('Gilroy'), local('Gilroy Regular'), local('Gilroy-Regular');
        font-weight: 400;
        font-style: normal;
    }
    @font-face {
        font-family: 'LTC Gilroy';
        src: local('Gilroy Bold'), local('Gilroy-Bold'), local('Gilroy SemiBold'), local('Gilroy-SemiBold');
        font-weight: 700;
        font-style: normal;
    }

    :root { --ltc-font: 'LTC Gilroy', 'Gilroy', 'Gilroy Regular', Arial, sans-serif; }
    html,
    body,
    main,
    table,
    thead,
    tbody,
    tr,
    th,
    td,
    div,
    span,
    p,
    h1,
    header,
    section,
    footer,
    button,
    a,
    .ltc-page,
    .ltc-page *,
    .print-toolbar,
    .print-toolbar * {
        font-family: var(--ltc-font) !important;
        font-synthesis: none;
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
    }
    body { margin: 0; color: #1f2933; background: #eef2f7; font-family: var(--ltc-font) !important; font-weight: 400; }
    .print-toolbar { position: sticky; top: 0; z-index: 20; background: rgba(241,245,249,.96); border-bottom: 1px solid #cbd5e1; }
    .print-toolbar-inner { max-width: 900px; margin: 0 auto; min-height: 54px; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 8px 10px; }
    .print-toolbar-title { color: #334155; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .print-toolbar-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
    .print-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 34px; border: 1px solid #cbd5e1; border-radius: 9px; background: #fff; color: #0f172a; padding: 0 12px; font-size: 11px; font-weight: 700; text-decoration: none; cursor: pointer; }
    .print-btn.primary { border-color: #111827; background: #111827; color: #fff; }
    .ltc-page { font-weight: 400; width: 794px; min-height: 1123px; margin: 14px auto 24px; padding: 26px 44px 20px; background: #fff; box-shadow: 0 18px 45px rgba(15,23,42,.18); box-sizing: border-box; position: relative; font-size: 11px; line-height: 1.18; }
    .top-form-no { text-align: right; font-size: 7.5px; font-weight: 700; margin-bottom: 4px; }
    .official-header { display: grid; grid-template-columns: 168px 1fr; gap: 12px; align-items: center; border-bottom: 3px solid #333; padding-bottom: 5px; }
    .logos { display: flex; gap: 8px; align-items: center; }
    .logo-img { height: 58px; max-width: 78px; object-fit: contain; }
    .logo-fallback { width: 74px; height: 54px; display: flex; align-items: center; justify-content: center; border: 1px solid #cbd5e1; color: #64748b; font-size: 10px; font-weight: 700; text-align: center; }
    .agency-lines .republic { font-size: 16px; letter-spacing: .02em; }
    .agency-lines .department { font-size: 24px; font-weight: 700; letter-spacing: .015em; text-transform: uppercase; }
    .agency-lines .tagline { color: #5f8b41; font-size: 15px; font-weight: 700; }
    .ltc-number-row { display: flex; justify-content: flex-end; margin: 9px 0 10px; }
    .ltc-number { display: inline-flex; gap: 8px; align-items: center; border: 2px solid #9ac6df; background: #edf7fb; padding: 3px 9px; font-size: 10px; }
    .title { text-align: center; margin: 6px 0 14px; }
    .title h1 { margin: 0; font-size: 25px; letter-spacing: .03em; font-weight: 700; color: #4b5563; }
    .title p { margin: 1px 0 0; font-size: 13px; color: #4b5563; }
    .intro { width: 610px; margin: 0 auto 9px; font-style: italic; color: #374151; }
    .detail-table { width: 610px; margin: 0 auto; border-collapse: collapse; }
    .detail-table td { padding: 4px 3px; vertical-align: top; }
    .detail-label { width: 116px; text-align: right; color: #4b5563; font-style: italic; padding-right: 12px !important; white-space: nowrap; }
    .detail-value { font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }
    .decision-line { width: 610px; margin: 154px auto 14px; display: flex; align-items: center; gap: 12px; }
    .decision-line .prefix { color: #4b5563; }
    .decision-box { min-width: 128px; padding: 9px 18px; border: 3px solid #4b5563; text-align: center; font-weight: 700; letter-spacing: .05em; }
    .basis { width: 610px; margin: 0 auto; text-align: left; color: #4b5563; font-size: 11px; }
    .basis p { margin: 0 0 4px; }
    .issued-line { width: 610px; margin: 18px auto 12px; color: #4b5563; }
    .issued-line .blank { display: inline-block; min-width: 170px; border-bottom: 1px solid #4b5563; color: #111827; padding: 0 8px 1px; }
    .lower-area { width: 610px; margin: 0 auto; display: grid; grid-template-columns: 310px 1fr; gap: 22px; align-items: end; }
    .payment-table { width: 250px; border-collapse: collapse; margin-top: 0; font-size: 10px; color: #4b5563; }
    .payment-table td { border: 1px solid #9ca3af; padding: 3px 5px; }
    .payment-table .head { font-style: italic; font-weight: 700; text-decoration: underline; }
    .signature { text-align: center; color: #374151; }
    .signatory { font-size: 13px; font-weight: 700; }
    .signatory-title { font-size: 11px; }
    .warning-box { width: 610px; margin: 5px auto 0; border: 1px solid #4b5563; padding: 6px 9px; font-size: 10.5px; color: #4b5563; }
    .warning-box strong { color: #111827; }
    .green-bars { width: 610px; margin: 3px auto 6px; display: grid; grid-template-columns: 1fr 1fr; gap: 105px; border-bottom: 3px solid #4b5563; }
    .green-bar { background: #79c35a; color: #fff; font-size: 11px; font-weight: 700; text-align: center; padding: 4px 6px; text-transform: uppercase; }
    .footer { width: 610px; margin: 0 auto; display: grid; grid-template-columns: 1fr 190px; gap: 20px; font-size: 11px; color: #4b5563; }
    @media print { body { background: #fff; } .print-toolbar { display: none !important; } .ltc-page { margin: 0; padding: 0; box-shadow: none; width: auto; min-height: auto; } }
</style>

@if ($showToolbar)
    <div class="print-toolbar">
        <div class="print-toolbar-inner">
            <div class="print-toolbar-title">LTC Form No. 5 Print View</div>
            <div class="print-toolbar-actions">
                <a href="{{ $returnRoute }}" class="print-btn">{{ $returnLabel }}</a>
                <button type="button" onclick="window.print()" class="print-btn primary">Print / Save as PDF</button>
            </div>
        </div>
    </div>
@endif

<main class="ltc-page" style="font-family: 'LTC Gilroy', 'Gilroy', 'Gilroy Regular', Arial, sans-serif !important; font-weight: 400;">
    <div class="top-form-no">LTC Form No. 5</div>

    <header class="official-header">
        <div class="logos">
            @if ($darLogo)
                <img src="{{ $darLogo }}" class="logo-img" alt="DAR Logo">
            @else
                <div class="logo-fallback">DAR</div>
            @endif
            @if ($bagongLogo)
                <img src="{{ $bagongLogo }}" class="logo-img" alt="Bagong Pilipinas Logo">
            @else
                <div class="logo-fallback">BAGONG<br>PILIPINAS</div>
            @endif
        </div>
        <div class="agency-lines">
            <div class="republic">REPUBLIC OF THE PHILIPPINES</div>
            <div class="department">DEPARTMENT OF AGRARIAN REFORM</div>
            <div class="tagline">Tunay na Pagbabago sa Repormang Agraryo</div>
        </div>
    </header>

    <div class="ltc-number-row">
        <div class="ltc-number"><strong>LTC No.</strong> <span>{{ $clearance->clearance_number }}</span></div>
    </div>

    <div class="title">
        <h1>CERTIFICATION</h1>
        <p>(Land Transfer Clearance)</p>
    </div>

    <div class="intro">
        This is to certify that the application/request for issuance of Land Transfer Clearance (LTC)<br>
        filed to this Office in the name of:
    </div>

    <table class="detail-table">
        <tr><td class="detail-label">(Owner)</td><td class="detail-value">{{ $ownerName ?: '__________' }}</td></tr>
        <tr><td class="detail-label">(Title/TD Number)</td><td class="detail-value">{{ $titleType }} No. {{ $titleNo }} / TD Number {{ $taxDeclNo }}</td></tr>
        <tr><td class="detail-label">(Lot Number)</td><td class="detail-value">{{ $lotNo }}, {{ $surveyNo }}, with an area of {{ $areaText }}</td></tr>
        <tr><td class="detail-label">(Location)</td><td class="detail-value">{{ $location }}</td></tr>
        <tr><td class="detail-label">(subject of)</td><td class="detail-value">{{ $subjectLine }}</td></tr>
        <tr><td class="detail-label">(notarized as)</td><td class="detail-value">{{ $notarialLine }}</td></tr>
        <tr><td class="detail-label">(by)</td><td class="detail-value">{{ $notary ?: '__________' }}</td></tr>
        <tr><td class="detail-label">(transferor/s)</td><td class="detail-value">{{ $transferorNames ?: '__________' }}</td></tr>
        <tr><td class="detail-label">(transferee/s)</td><td class="detail-value">{{ $transfereeNames ?: '__________' }}</td></tr>
    </table>

    <div class="decision-line">
        <span class="prefix">is hereby</span>
        <span class="decision-box">{{ $decisionLabel }}</span>
    </div>

    <section class="basis">
        <p>based on the attestation of the CARPO/LTS/FOD and from the report and recommendation of the Chief Legal Division/Authorized Legal Officer pursuant to Administrative Order (A.O.) No. 4, Series of 2021.</p>
        <p>Any actual change in the use of the land and/or development over the subject land, require a prior Order of Conversion or Exemption/Exclusion from the office of the DAR Regional Director.</p>
        <p>This Office reserves the right to revoke this Certification of LTC in case of findings of misrepresentation or submission of falsified documents by either or both parties to the Deed of transfer and any third person who may be affected by the transfer.</p>
        <p>This Certification, which is valid for six (6) months from the date of issuance, is hereby issued only for the purpose stated in the application/request for issuance of LTC.</p>
    </section>

    <div class="issued-line">
        Issued on <span class="blank">{{ $issueDate ? $issueDate->format('F d, Y') : '' }}</span>
        at the Provincial Agrarian Reform Office of Negros Oriental in Dumaguete City.
    </div>

    <div class="lower-area">
        <div>
            <table class="payment-table">
                <tr><td class="head" colspan="2">Certification Fee</td></tr>
                <tr><td>O.R. No. :</td><td>{{ $application->or_number ?: '__________' }}</td></tr>
                <tr><td>Date :</td><td>{{ $application->or_date ? $application->or_date->format('m/d/Y') : '__________' }}</td></tr>
                <tr><td>Amount :</td><td>{{ $application->amount_paid ? rtrim(rtrim(number_format((float) $application->amount_paid, 2, '.', ''), '0'), '.') : '__________' }}</td></tr>
            </table>
        </div>
        <div class="signature">
            <div class="signatory">ENGR. MANUEL M. GALON, JR.</div>
            <div class="signatory-title">OIC Provincial Agrarian Reform Program Officer II</div>
        </div>
    </div>

    <div class="warning-box">
        <em>This clearance issued is <strong>NOT VALID</strong> for purposes of ejecting agricultural tenants, if there are any. Likewise, this is <strong>NOT VALID</strong> to effect change of land classification in the tax declaration from AGRICULTURAL to NON-AGRICULTURAL, which requires DAR LAND USE CONVERSION ORDER.</em>
    </div>

    <div class="green-bars">
        <div class="green-bar">This clearance is valid only for six<br>(6) months from date of issuance.</div>
        <div class="green-bar">Not official if not sealed</div>
    </div>

    <footer class="footer">
        <div>
            Provincial Agrarian Reform Office of Negros Oriental<br>
            The Market Place, South Calindagan Road<br>
            Dumaguete City
        </div>
        <div>
            (035) 522-7144<br>
            www.dar.gov.ph
        </div>
    </footer>
</main>
