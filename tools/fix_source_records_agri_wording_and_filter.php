<?php

$root = getcwd();

function file_path(string $relative): string
{
    return getcwd() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
}

function read_file(string $relative): string
{
    $path = file_path($relative);
    if (! file_exists($path)) {
        echo "SKIP missing: {$relative}\n";
        return '';
    }

    return file_get_contents($path);
}

function write_file(string $relative, string $contents): void
{
    $path = file_path($relative);
    if (! file_exists($path)) {
        echo "SKIP missing: {$relative}\n";
        return;
    }

    file_put_contents($path, $contents);
    echo "UPDATED: {$relative}\n";
}

function replace_once(string $haystack, string $needle, string $replacement, string $label): string
{
    if (str_contains($haystack, $replacement)) {
        echo "OK already patched: {$label}\n";
        return $haystack;
    }

    if (! str_contains($haystack, $needle)) {
        echo "WARN pattern not found: {$label}\n";
        return $haystack;
    }

    return str_replace($needle, $replacement, $haystack);
}

// 1) Fix staff parcel agricultural_status filtering after form selector removal.
$controller = 'app/Http/Controllers/Staff/RecordSearchController.php';
$c = read_file($controller);
if ($c !== '') {
    $c = replace_once(
        $c,
        "'status' => ['nullable', 'string', 'max:50'],",
        "'status' => ['nullable', 'string', 'max:50'],\n            'agricultural_status' => ['nullable', 'string', Rule::in(array_keys(Parcel::AGRICULTURAL_STATUSES))],",
        'RecordSearchController parcel filter validation'
    );

    $c = replace_once(
        $c,
        "if (! empty(\$filters['status'])) {\n            \$parcelsQuery->where('status', \$filters['status']);\n        }",
        "if (! empty(\$filters['status'])) {\n            \$parcelsQuery->where('status', \$filters['status']);\n        }\n\n        if (! empty(\$filters['agricultural_status'])) {\n            \$parcelsQuery->where('agricultural_status', \$filters['agricultural_status']);\n        }",
        'RecordSearchController agricultural_status where clause'
    );

    write_file($controller, $c);
}

// 2) Replace source-record Crop/Land Use wording in all relevant source record pages.
$viewReplacements = [
    'resources/views/staff/source-record-packages/create.blade.php' => [
        'Crop / Land Use' => 'Agricultural Classification Notes',
        'id="crop_or_land_use" name="crop_or_land_use" value="{{ old(\'crop_or_land_use\') }}" class="w-full rounded-lg border-gray-300 text-sm"' => 'id="crop_or_land_use" name="crop_or_land_use" value="{{ old(\'crop_or_land_use\') }}" placeholder="e.g. Private agricultural, CLOA, CARP-covered, or other source note" class="w-full rounded-lg border-gray-300 text-sm"',
    ],
    'resources/views/staff/legacy-records/create.blade.php' => [
        'Crop / Land Use' => 'Agricultural Classification Notes',
        'crop_or_land_use' => 'crop_or_land_use',
    ],
    'resources/views/staff/legacy-records/show.blade.php' => [
        'Area / Land Use' => 'Area / Agricultural Classification',
        'No land use recorded' => 'No agricultural classification note recorded',
    ],
    'resources/views/staff/source-record-packages/show.blade.php' => [
        'Crop / Land Use' => 'Agricultural Classification Notes',
        'Land Use' => 'Agricultural Classification',
        'No land use recorded' => 'No agricultural classification note recorded',
    ],
];

foreach ($viewReplacements as $relative => $replacements) {
    $content = read_file($relative);
    if ($content === '') {
        continue;
    }

    $original = $content;
    foreach ($replacements as $from => $to) {
        $content = str_replace($from, $to, $content);
    }

    // Add placeholder to legacy single source record field if it has the plain input.
    if ($relative === 'resources/views/staff/legacy-records/create.blade.php') {
        $content = preg_replace(
            '/<input\s+name="crop_or_land_use"\s+value="\{\{\s*old\(\'crop_or_land_use\'\)\s*\}\}"\s+class="([^"]*)">/',
            '<input name="crop_or_land_use" value="{{ old(\'crop_or_land_use\') }}" placeholder="e.g. Private agricultural, CLOA, CARP-covered, or other source note" class="$1">',
            $content
        );
    }

    if ($content !== $original) {
        write_file($relative, $content);
    } else {
        echo "OK no source wording changes needed: {$relative}\n";
    }
}

// 3) Make import template/header wording less generic if controller still exposes crop_or_land_use.
$importController = 'app/Http/Controllers/Staff/SourceRecordPackageImportController.php';
$ic = read_file($importController);
if ($ic !== '') {
    // Keep DB/input key as crop_or_land_use for compatibility; only generated CSV labels/help text in views should change.
    $icOriginal = $ic;
    $ic = str_replace('crop_or_land_use', 'crop_or_land_use', $ic);
    if ($ic !== $icOriginal) {
        write_file($importController, $ic);
    } else {
        echo "OK import controller key preserved for compatibility.\n";
    }
}

// 4) If parcel edit/update controller still keeps not_yet_determined on form update, default missing field to private_agricultural.
$possibleControllers = [
    'app/Http/Controllers/Staff/ParcelRecordController.php',
    'app/Http/Controllers/Staff/RecordSearchController.php',
];
foreach ($possibleControllers as $relative) {
    $content = read_file($relative);
    if ($content === '') {
        continue;
    }

    $original = $content;

    // If an update method validates without agricultural_status and updates parcel from $validated,
    // make sure missing agricultural_status is set. This is intentionally conservative and idempotent.
    if (str_contains($content, "Parcel::AGRICULTURAL_STATUSES") && ! str_contains($content, "default missing agricultural status after form removal")) {
        $content = str_replace(
            "\$parcel->update(\$validated);",
            "// default missing agricultural status after form removal\n        \$validated['agricultural_status'] = \$validated['agricultural_status'] ?? 'private_agricultural';\n\n        \$parcel->update(\$validated);",
            $content
        );
    }

    if ($content !== $original) {
        write_file($relative, $content);
    }
}

echo "\nDone. Run: php artisan view:clear && php artisan route:clear && php artisan test --filter=ParcelAgriculturalStatusTest && php artisan test\n";
