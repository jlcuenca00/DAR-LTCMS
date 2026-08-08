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

        // For the official PDF, prefer the Bagong Pilipinas logo kept in
        // storage/app/public. This deliberately happens after the shared Form 5
        // partial renders so the normal browser/print view remains unchanged.
        $storageDisk = \Illuminate\Support\Facades\Storage::disk('public');
        $bagongStoragePath = null;
        $bagongFilenames = [
            'bagong-pilipinas-logo.svg',
            'bagong-pilipinas.svg',
            'bagong-pilipinas-logo.jpg',
            'bagong-pilipinas.jpg',
            'bagong-pilipinas-logo.jpeg',
            'bagong-pilipinas.jpeg',
            'bagong-pilipinas-logo.png',
            'bagong-pilipinas.png',
            'bagong_pilipinas_logo.png',
            'bagong_pilipinas.png',
        ];

        foreach ($bagongFilenames as $filename) {
            foreach ([$filename, 'images/' . $filename, 'logos/' . $filename, 'clearance/' . $filename, 'clearances/' . $filename] as $candidate) {
                if ($storageDisk->exists($candidate)) {
                    $bagongStoragePath = $candidate;
                    break 2;
                }
            }
        }

        if (! $bagongStoragePath) {
            $bagongStoragePath = collect($storageDisk->allFiles())
                ->first(function ($path) {
                    $basename = strtolower(pathinfo($path, PATHINFO_FILENAME));
                    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                    return str_contains($basename, 'bagong')
                        && str_contains($basename, 'pilipinas')
                        && in_array($extension, ['svg', 'jpg', 'jpeg', 'png'], true);
                });
        }

        if ($bagongStoragePath) {
            $bagongAbsolutePath = $storageDisk->path($bagongStoragePath);
            $replacement = '<img src="' . e($bagongAbsolutePath) . '" class="logo-img" alt="Bagong Pilipinas Logo">';

            $form5Html = preg_replace(
                '/<img\s+src="[^"]*"\s+class="logo-img"\s+alt="Bagong Pilipinas Logo">/i',
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

        /* @page already owns the physical folio margins. The Form 5 canvas
           must fill only that printable area, not another full 8.5-inch page. */
        .pdf-output .ltc-page {
            width: auto !important;
            max-width: 100% !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            box-sizing: border-box !important;
        }

        .pdf-output .official-header {
            width: 100% !important;
            box-sizing: border-box !important;
        }
    </style>
</body>
</html>
