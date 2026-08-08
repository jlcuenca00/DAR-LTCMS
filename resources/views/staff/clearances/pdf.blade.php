<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $clearance->clearance_number }}</title>
</head>
<body class="pdf-output">
    @php
        $form5Html = view('staff.clearances.partials.form5-content', [
            'application' => $application,
            'clearance' => $clearance,
            'showToolbar' => false,
            'pdfMode' => true,
        ])->render();

        // Form No. 5 must use the official seal-style Bagong Pilipinas PNG.
        // Prefer the public storage copy, then fall back to public/images.
        $storageDisk = \Illuminate\Support\Facades\Storage::disk('public');
        $bagongStoragePath = collect([
            'bagong-pilipinas.png',
            'images/bagong-pilipinas.png',
            'logos/bagong-pilipinas.png',
            'clearance/bagong-pilipinas.png',
            'clearances/bagong-pilipinas.png',
        ])->first(fn ($candidate) => $storageDisk->exists($candidate));

        $bagongAbsolutePath = $bagongStoragePath
            ? $storageDisk->path($bagongStoragePath)
            : public_path('images/bagong-pilipinas.png');

        if (is_file($bagongAbsolutePath)) {
            $replacement = '<img src="' . e($bagongAbsolutePath) . '" class="logo-img" alt="Bagong Pilipinas Logo">';

            $form5Html = preg_replace(
                '/(?:<img\s+src="[^"]*"\s+class="logo-img"\s+alt="Bagong Pilipinas Logo">|<div\s+class="logo-fallback">BAGONG<br\s*\/?>PILIPINAS<\/div>)/i',
                $replacement,
                $form5Html,
                1
            ) ?? $form5Html;
        }
    @endphp

    {!! $form5Html !!}

    <style>
        @page {
            size: 8.5in 13in;
            margin: 10mm 12mm 10mm 12mm;
        }

        html,
        body.pdf-output {
            margin: 0;
            padding: 0;
            width: 100%;
        }

        /* Keep the official form inside the folio printable area. */
        .pdf-output .ltc-page {
            width: auto !important;
            max-width: 100% !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            box-sizing: border-box !important;
        }

        .pdf-output .top-form-no {
            margin: 0 4px 5px 0 !important;
            padding-top: 2px !important;
            font-size: 7px !important;
            line-height: 1 !important;
        }

        /* Compact institutional header based on the supplied DAR Form No. 5. */
        .pdf-output .official-header {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            box-sizing: border-box !important;
            padding: 0 0 6px !important;
            border-bottom: 3px solid #333 !important;
        }

        .pdf-output .logos {
            display: table-cell !important;
            width: 150px !important;
            padding: 0 10px 0 3px !important;
            vertical-align: middle !important;
            white-space: nowrap !important;
            box-sizing: border-box !important;
        }

        .pdf-output .agency-lines {
            display: table-cell !important;
            vertical-align: middle !important;
            padding: 0 0 0 6px !important;
        }

        .pdf-output .logo-img {
            display: inline-block !important;
            vertical-align: middle !important;
            margin: 0 6px 0 0 !important;
            object-fit: contain !important;
        }

        .pdf-output .logo-img[alt="DAR Logo"] {
            height: 61px !important;
            max-width: 70px !important;
        }

        .pdf-output .logo-img[alt="Bagong Pilipinas Logo"] {
            height: 57px !important;
            max-width: 68px !important;
        }

        .pdf-output .agency-lines .republic {
            font-size: 13px !important;
            line-height: 1.04 !important;
            letter-spacing: .015em !important;
            white-space: nowrap !important;
        }

        .pdf-output .agency-lines .department {
            font-size: 18px !important;
            line-height: 1.05 !important;
            letter-spacing: 0 !important;
            white-space: nowrap !important;
        }

        .pdf-output .agency-lines .tagline {
            margin-top: 2px !important;
            font-size: 11.5px !important;
            line-height: 1.04 !important;
            white-space: nowrap !important;
        }

        .pdf-output .ltc-number-row {
            margin: 8px 2px 14px 0 !important;
        }

        .pdf-output .ltc-number {
            padding: 3px 8px !important;
            font-size: 9px !important;
        }

        .pdf-output .title {
            margin: 3px 0 14px !important;
        }

        .pdf-output .title h1 {
            font-size: 24px !important;
        }

        /* Keep the lower official elements compact and fully inside one folio page. */
        .pdf-output .issued-line {
            margin-top: 13px !important;
            margin-bottom: 8px !important;
        }

        .pdf-output .lower-area {
            display: table !important;
            width: 610px !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
        }

        .pdf-output .lower-area > div {
            display: table-cell !important;
            vertical-align: bottom !important;
        }

        .pdf-output .lower-area > div:first-child {
            width: 300px !important;
            padding-right: 24px !important;
        }

        .pdf-output .payment-table {
            margin-bottom: 0 !important;
        }

        .pdf-output .signature {
            padding-bottom: 3px !important;
        }

        .pdf-output .warning-box {
            width: 610px !important;
            box-sizing: border-box !important;
            margin: 5px auto 0 !important;
            padding: 5px 8px !important;
            font-size: 9.5px !important;
            line-height: 1.2 !important;
        }

        /* Render the official green validity strip with table cells. DomPDF
           handles table layout much more reliably than flex/float/inline-block. */
        .pdf-output .green-bars {
            display: table !important;
            width: 610px !important;
            table-layout: fixed !important;
            margin: 3px auto 0 !important;
            padding: 0 !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            border-bottom: 3px solid #4b5563 !important;
            overflow: visible !important;
            box-sizing: border-box !important;
        }

        .pdf-output .green-bar {
            display: table-cell !important;
            float: none !important;
            width: 251px !important;
            height: 25px !important;
            margin: 0 !important;
            padding: 3px 5px !important;
            box-sizing: border-box !important;
            vertical-align: middle !important;
            background: #79c35a !important;
            color: #fff !important;
            font-size: 8.6px !important;
            font-weight: 700 !important;
            line-height: 1.05 !important;
            text-align: center !important;
            text-transform: uppercase !important;
        }

        .pdf-output .green-bar:last-child {
            border-left: 108px solid #fff !important;
        }

        /* Keep the footer clearly separated from the validity strip and keep
           office/address information on the left and contact details on right. */
        .pdf-output .footer {
            display: table !important;
            width: 610px !important;
            table-layout: fixed !important;
            margin: 7px auto 0 !important;
            font-size: 9.5px !important;
            line-height: 1.35 !important;
            color: #4b5563 !important;
        }

        .pdf-output .footer > div {
            display: table-cell !important;
            vertical-align: top !important;
        }

        .pdf-output .footer > div:first-child {
            width: 430px !important;
            padding-right: 12px !important;
            white-space: nowrap !important;
        }

        .pdf-output .footer > div:last-child {
            width: 150px !important;
            padding-left: 18px !important;
            text-align: right !important;
            white-space: nowrap !important;
        }
    </style>
</body>
</html>
