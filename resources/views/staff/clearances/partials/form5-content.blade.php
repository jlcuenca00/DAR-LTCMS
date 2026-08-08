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

    $logoDataUri = function (array $filenames) {
        foreach ($filenames as $filename) {
            $path = public_path('images/' . $filename);
            if (! is_file($path)) {
                continue;
            }

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                default => 'image/png',
            };

            $contents = @file_get_contents($path);
            if ($contents !== false) {
                return 'data:' . $mime . ';base64,' . base64_encode($contents);
            }
        }

        return null;
    };

    $darLogo = $logoDataUri(['dar-logo.png', 'dar-logo.jpg', 'dar-logo.svg']);
    $bagongLogo = $logoDataUri(['bagong-pilipinas.png', 'bagong-pilipinas.jpg', 'bagong-pilipinas.svg', 'bagong-pilipinas-logo.svg']);
@endphp

<style>
    @page { size: A4; margin: 9mm 11mm; }

    :root {
        --ltc-font: 'Gilroy', 'Gilroy Regular', 'DejaVu Sans', Arial, sans-serif;
        --ltc-green: #14532d;
        --ltc-green-2: #166534;
        --ltc-soft: #f4f8f5;
        --ltc-line: #cbd5cf;
        --ltc-ink: #17211b;
        --ltc-muted: #55635a;
    }

    html, body, main, table, thead, tbody, tr, th, td, div, span, p, h1,
    header, section, footer, button, a, .ltc-page, .ltc-page *,
    .print-toolbar, .print-toolbar * {
        font-family: var(--ltc-font) !important;
        font-synthesis: none;
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
    }

    body {
        margin: 0;
        color: var(--ltc-ink);
        background: #e9efeb;
        font-family: var(--ltc-font) !important;
        font-weight: 400;
    }

    .print-toolbar {
        position: sticky;
        top: 0;
        z-index: 20;
        background: rgba(255,255,255,.97);
        border-bottom: 1px solid #d8e0db;
        box-shadow: 0 5px 18px rgba(15,23,42,.06);
    }

    .print-toolbar-inner {
        max-width: 920px;
        margin: 0 auto;
        min-height: 58px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 8px 14px;
    }

    .print-toolbar-title {
        color: var(--ltc-green);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .09em;
        text-transform: uppercase;
    }

    .print-toolbar-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .print-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        border: 1px solid #cbd5d1;
        border-radius: 9px;
        background: #fff;
        color: #1f2937;
        padding: 0 13px;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
    }

    .print-btn:hover { background: #f4f8f5; border-color: #9fbea9; color: var(--ltc-green); }
    .print-btn.primary { border-color: var(--ltc-green-2); background: var(--ltc-green-2); color: #fff; }
    .print-btn.primary:hover { background: var(--ltc-green); }

    .ltc-page {
        width: 794px;
        min-height: 1123px;
        margin: 18px auto 28px;
        padding: 28px 42px 22px;
        background: #fff;
        border: 1px solid #d9e2dc;
        box-shadow: 0 20px 55px rgba(15,23,42,.16);
        box-sizing: border-box;
        position: relative;
        font-size: 10.8px;
        line-height: 1.28;
    }

    .top-form-no {
        text-align: right;
        color: #66756c;
        font-size: 7.5px;
        font-weight: 700;
        letter-spacing: .06em;
        margin-bottom: 6px;
    }

    .official-header {
        display: table;
        width: 100%;
        table-layout: fixed;
        border-bottom: 3px solid var(--ltc-green);
        padding-bottom: 8px;
    }

    .logos {
        display: table-cell;
        width: 158px;
        vertical-align: middle;
        white-space: nowrap;
    }

    .logo-img {
        display: inline-block;
        height: 55px;
        max-width: 72px;
        margin-right: 6px;
        object-fit: contain;
        vertical-align: middle;
    }

    .logo-fallback {
        display: inline-block;
        width: 66px;
        height: 48px;
        border: 1px solid #cbd5e1;
        color: #64748b;
        font-size: 9px;
        font-weight: 700;
        line-height: 48px;
        text-align: center;
        vertical-align: middle;
    }

    .agency-lines {
        display: table-cell;
        vertical-align: middle;
        padding-left: 12px;
    }

    .agency-lines .republic {
        color: #3f4d44;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .045em;
    }

    .agency-lines .department {
        color: var(--ltc-green);
        font-size: 21px;
        font-weight: 700;
        letter-spacing: .01em;
        line-height: 1.05;
        text-transform: uppercase;
    }

    .agency-lines .tagline {
        margin-top: 3px;
        color: #5b7f44;
        font-size: 11px;
        font-weight: 600;
        font-style: italic;
    }

    .ltc-number-row { text-align: right; margin: 10px 0 11px; }

    .ltc-number {
        display: inline-block;
        border: 1px solid #b9cfc0;
        border-left: 4px solid var(--ltc-green-2);
        background: var(--ltc-soft);
        color: #243229;
        padding: 5px 10px;
        font-size: 9.5px;
    }

    .ltc-number strong { color: var(--ltc-green); margin-right: 7px; }

    .title { text-align: center; margin: 8px 0 15px; }
    .title h1 {
        margin: 0;
        color: var(--ltc-green);
        font-size: 23px;
        line-height: 1;
        letter-spacing: .08em;
        font-weight: 700;
    }
    .title p { margin: 4px 0 0; color: #445249; font-size: 11px; font-weight: 600; }

    .intro {
        width: 612px;
        margin: 0 auto 9px;
        color: #3f4d44;
        font-style: italic;
        line-height: 1.4;
    }

    .detail-table {
        width: 612px;
        margin: 0 auto;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #d7e0da;
        background: #fff;
    }

    .detail-table td {
        padding: 5px 8px;
        vertical-align: top;
        border-bottom: 1px solid #edf1ee;
    }
    .detail-table tr:last-child td { border-bottom: 0; }

    .detail-label {
        width: 118px;
        color: #5d6b62;
        background: #f7faf8;
        font-size: 9.5px;
        font-weight: 600;
        padding-right: 10px !important;
        white-space: nowrap;
    }

    .detail-value {
        color: #17211b;
        font-weight: 600;
        text-decoration: none;
    }

    .decision-line {
        width: 612px;
        margin: 116px auto 14px;
        text-align: center;
    }

    .decision-line .prefix {
        display: inline-block;
        margin-right: 10px;
        color: #4c5b51;
        font-size: 11px;
        vertical-align: middle;
    }

    .decision-box {
        display: inline-block;
        min-width: 132px;
        padding: 9px 20px;
        border: 2px solid {{ $isGranted ? '#166534' : '#991b1b' }};
        background: {{ $isGranted ? '#eff8f2' : '#fff1f2' }};
        color: {{ $isGranted ? '#14532d' : '#991b1b' }};
        text-align: center;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .08em;
    }

    .basis {
        width: 612px;
        margin: 0 auto;
        color: #46544b;
        font-size: 9.8px;
        line-height: 1.38;
        text-align: justify;
    }
    .basis p { margin: 0 0 5px; }

    .issued-line {
        width: 612px;
        margin: 16px auto 13px;
        color: #445249;
        line-height: 1.4;
    }

    .issued-line .blank {
        display: inline-block;
        min-width: 165px;
        border-bottom: 1px solid #5f6d64;
        color: #17211b;
        padding: 0 7px 1px;
        font-weight: 600;
        text-align: center;
    }

    .lower-area {
        width: 612px;
        margin: 0 auto;
        display: table;
        table-layout: fixed;
    }

    .lower-area > div {
        display: table-cell;
        width: 50%;
        vertical-align: bottom;
    }

    .payment-table {
        width: 255px;
        border-collapse: collapse;
        font-size: 9.5px;
        color: #445249;
    }

    .payment-table td { border: 1px solid #b8c4bc; padding: 4px 6px; }
    .payment-table .head { background: #f1f7f3; color: var(--ltc-green); font-weight: 700; text-decoration: none; }

    .signature { text-align: center; color: #27352c; padding-left: 18px; }
    .signatory { padding-top: 20px; border-top: 1px solid #5d6b62; font-size: 11.5px; font-weight: 700; }
    .signatory-title { margin-top: 2px; color: #5c6a61; font-size: 9.5px; line-height: 1.3; }

    .warning-box {
        width: 612px;
        margin: 10px auto 0;
        border: 1px solid #c7b56e;
        border-left: 4px solid #b08900;
        background: #fffaf0;
        padding: 7px 9px;
        font-size: 9.2px;
        line-height: 1.34;
        color: #574b25;
    }
    .warning-box strong { color: #7a1f1f; }

    .green-bars {
        width: 612px;
        margin: 7px auto 7px;
        display: table;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 7px 0;
    }

    .green-bar {
        display: table-cell;
        width: 50%;
        background: var(--ltc-green-2);
        color: #fff;
        font-size: 9.5px;
        font-weight: 700;
        line-height: 1.25;
        text-align: center;
        padding: 6px 7px;
        text-transform: uppercase;
        vertical-align: middle;
    }

    .footer {
        width: 612px;
        margin: 0 auto;
        padding-top: 7px;
        border-top: 1px solid #b8c4bc;
        display: table;
        table-layout: fixed;
        font-size: 9.5px;
        line-height: 1.38;
        color: #59675e;
    }

    .footer > div { display: table-cell; vertical-align: top; }
    .footer > div:last-child { width: 180px; text-align: right; }

    @media screen and (max-width: 860px) {
        .ltc-page {
            width: calc(100% - 24px);
            min-height: 0;
            margin: 12px;
            padding: 22px 18px;
            overflow: hidden;
        }
        .official-header, .lower-area, .green-bars, .footer { width: 100%; }
        .intro, .detail-table, .decision-line, .basis, .issued-line, .warning-box { width: 100%; }
        .agency-lines .department { font-size: 17px; }
        .decision-line { margin-top: 44px; }
        .print-toolbar-inner { align-items: flex-start; flex-direction: column; }
        .print-toolbar-actions { width: 100%; }
        .print-btn { flex: 1 1 auto; }
    }

    @media print {
        body { background: #fff; }
        .print-toolbar { display: none !important; }
        .ltc-page {
            margin: 0;
            padding: 0;
            border: 0;
            box-shadow: none;
            width: auto;
            min-height: auto;
        }
    }
</style>

@if ($showToolbar)
    <div class="print-toolbar">
        <div class="print-toolbar-inner">
            <div class="print-toolbar-title">LTC Form No. 5 · {{ $clearance->clearance_number }}</div>
            <div class="print-toolbar-actions">
                <a href="{{ $returnRoute }}" class="print-btn">{{ $returnLabel }}</a>
                <a href="{{ auth()->user()?->role === 'landowner' ? route('landowner.applications.clearance.pdf', $application) : route('staff.applications.clearance.pdf', $application) }}" class="print-btn">Open PDF</a>
                <button type="button" onclick="window.print()" class="print-btn primary">Print</button>
            </div>
        </div>
    </div>
@endif

<main class="ltc-page" style="font-family: 'Gilroy', 'Gilroy Regular', 'DejaVu Sans', Arial, sans-serif !important; font-weight: 400;">
    <div class="top-form-no">LTC Form No. 5</div>

    <header class="official-header">
        <div class="logos">
            @if ($darLogo)
                <img src="{{ $darLogo }}" class="logo-img" alt="DAR Logo">
            @else
                <span class="logo-fallback">DAR</span>
            @endif
            @if ($bagongLogo)
                <img src="{{ $bagongLogo }}" class="logo-img" alt="Bagong Pilipinas Logo">
            @else
                <span class="logo-fallback">BAGONG PILIPINAS</span>
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
        <p>Land Transfer Clearance</p>
    </div>

    <div class="intro">
        This is to certify that the application/request for issuance of Land Transfer Clearance (LTC)<br>
        filed to this Office in the name of:
    </div>

    <table class="detail-table">
        <tr><td class="detail-label">Owner</td><td class="detail-value">{{ $ownerName ?: '__________' }}</td></tr>
        <tr><td class="detail-label">Title / TD Number</td><td class="detail-value">{{ $titleType }} No. {{ $titleNo }} / TD Number {{ $taxDeclNo }}</td></tr>
        <tr><td class="detail-label">Lot Number</td><td class="detail-value">{{ $lotNo }}, {{ $surveyNo }}, with an area of {{ $areaText }}</td></tr>
        <tr><td class="detail-label">Location</td><td class="detail-value">{{ $location }}</td></tr>
        <tr><td class="detail-label">Subject of</td><td class="detail-value">{{ $subjectLine }}</td></tr>
        <tr><td class="detail-label">Notarized as</td><td class="detail-value">{{ $notarialLine }}</td></tr>
        <tr><td class="detail-label">By</td><td class="detail-value">{{ $notary ?: '__________' }}</td></tr>
        <tr><td class="detail-label">Transferor/s</td><td class="detail-value">{{ $transferorNames ?: '__________' }}</td></tr>
        <tr><td class="detail-label">Transferee/s</td><td class="detail-value">{{ $transfereeNames ?: '__________' }}</td></tr>
    </table>

    <div class="decision-line">
        <span class="prefix">is hereby</span>
        <span class="decision-box">{{ $decisionLabel }}</span>
    </div>

    <section class="basis">
        <p>Based on the attestation of the CARPO/LTS/FOD and from the report and recommendation of the Chief Legal Division/Authorized Legal Officer pursuant to Administrative Order (A.O.) No. 4, Series of 2021.</p>
        <p>Any actual change in the use of the land and/or development over the subject land requires a prior Order of Conversion or Exemption/Exclusion from the office of the DAR Regional Director.</p>
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
                <tr><td>O.R. No.</td><td>{{ $application->or_number ?: '__________' }}</td></tr>
                <tr><td>Date</td><td>{{ $application->or_date ? $application->or_date->format('m/d/Y') : '__________' }}</td></tr>
                <tr><td>Amount</td><td>{{ $application->amount_paid ? rtrim(rtrim(number_format((float) $application->amount_paid, 2, '.', ''), '0'), '.') : '__________' }}</td></tr>
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
