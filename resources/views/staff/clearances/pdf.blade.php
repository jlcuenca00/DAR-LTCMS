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
            margin: 8mm 11mm;
        }

        html,
        body.pdf-output {
            margin: 0;
            padding: 0;
            width: 100%;
        }

        /* @page owns the physical folio margins. */
        .pdf-output .ltc-page {
            width: auto !important;
            max-width: 100% !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            box-sizing: border-box !important;
        }

        /* Match the official DAR Form No. 5 reference more closely. */
        .pdf-output .official-header {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            box-sizing: border-box !important;
            padding: 3px 0 6px !important;
        }

        .pdf-output .logos {
            display: table-cell !important;
            width: 142px !important;
            padding-left: 10px !important;
            padding-right: 8px !important;
            vertical-align: middle !important;
            white-space: nowrap !important;
            box-sizing: border-box !important;
        }

        .pdf-output .agency-lines {
            display: table-cell !important;
            vertical-align: middle !important;
            padding-left: 2px !important;
        }

        .pdf-output .logo-img {
            display: inline-block !important;
            vertical-align: middle !important;
            margin-right: 5px !important;
            object-fit: contain !important;
        }

        .pdf-output .logo-img[alt="DAR Logo"] {
            height: 54px !important;
            max-width: 63px !important;
        }

        .pdf-output .logo-img[alt="Bagong Pilipinas Logo"] {
            height: 47px !important;
            max-width: 60px !important;
        }

        .pdf-output .agency-lines .republic {
            font-size: 13px !important;
            line-height: 1.05 !important;
            white-space: nowrap !important;
        }

        .pdf-output .agency-lines .department {
            font-size: 18px !important;
            line-height: 1.05 !important;
            letter-spacing: .005em !important;
            white-space: nowrap !important;
        }

        .pdf-output .agency-lines .tagline {
            font-size: 12px !important;
            line-height: 1.05 !important;
            white-space: nowrap !important;
        }

        .pdf-output .ltc-number-row {
            margin: 7px 0 10px !important;
        }

        .pdf-output .title {
            margin-top: 4px !important;
        }
    </style>
</body>
</html>
