<!DOCTYPE html>
<html lang="en" style="--ltc-font: Arial, Helvetica, sans-serif;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>{{ $clearance->clearance_number }} | LTC Form No. 5 Print View</title>

    <link rel="preload" href="{{ asset('images/dar-logo.svg') }}" as="image" type="image/svg+xml">
    <link rel="preload" href="{{ asset('images/bagong-pilipinas.png') }}" as="image" type="image/png">
</head>
<body>
    @include('staff.clearances.partials.form5-content', [
        'showToolbar' => true,
        'returnRoute' => $returnRoute ?? null,
        'returnLabel' => $returnLabel ?? null,
    ])

    @include('staff.clearances.partials.form5-reference-styles')

    <script>
        // The legacy Form 5 partial still carries an inline Montserrat declaration.
        // Remove it in the browser print view so no remote font is needed to render.
        document.querySelector('.ltc-page')?.style.removeProperty('font-family');
    </script>
</body>
</html>
