<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $clearance->clearance_number }}</title>
</head>
<body class="pdf-output">
    @include('staff.clearances.partials.form5-content', [
        'showToolbar' => false,
        'pdfMode' => true,
    ])

    <style>
        @page {
            size: 8.5in 13in;
            margin: 8mm 11mm;
        }

        .pdf-output .ltc-page {
            width: 816px;
            min-height: 1248px;
        }
    </style>
</body>
</html>
