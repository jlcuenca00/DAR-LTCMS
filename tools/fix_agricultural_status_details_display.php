<?php

$root = dirname(__DIR__);

function patch_path(string $relative): string
{
    global $root;
    return $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
}

function fail_patch(string $message): void
{
    fwrite(STDERR, "ERROR: {$message}\n");
    exit(1);
}

$viewRelative = 'resources/views/staff/records/parcel-show.blade.php';
$viewPath = patch_path($viewRelative);

if (! file_exists($viewPath)) {
    fail_patch("{$viewRelative} not found. Make sure this patch is extracted into the Laravel project root.");
}

$contents = file_get_contents($viewPath);
$original = $contents;

$badge = <<<'BLADE'

        @php
            $agriculturalStatusLabel = $parcel->agricultural_status_label
                ?? \App\Models\Parcel::agriculturalStatusLabel($parcel->agricultural_status ?? null);
        @endphp

        <div data-agricultural-status-display class="mb-4 inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800">
            <i class="fa-solid fa-seedling text-emerald-700"></i>
            <span>Agricultural Status: {{ $agriculturalStatusLabel }}</span>
        </div>
BLADE;

// Keep this intentionally small: the previous phase already added the field/model.
// This only guarantees the staff parcel details page visibly renders the expected label.
if (! str_contains($contents, 'data-agricultural-status-display')) {
    // Best target: place inside the first staff panel/section so spacing remains consistent.
    if (preg_match('/(<section[^>]*class="[^"]*(?:staff-panel|parcel)[^"]*"[^>]*>)/', $contents, $matches, PREG_OFFSET_CAPTURE)) {
        $insertAt = $matches[0][1] + strlen($matches[0][0]);
        $contents = substr($contents, 0, $insertAt) . $badge . substr($contents, $insertAt);
    } elseif (preg_match('/(<main[^>]*>)/', $contents, $matches, PREG_OFFSET_CAPTURE)) {
        $insertAt = $matches[0][1] + strlen($matches[0][0]);
        $contents = substr($contents, 0, $insertAt) . $badge . substr($contents, $insertAt);
    } else {
        fail_patch('Could not safely locate a place to insert the agricultural status display badge.');
    }
}

// If the page has an old direct expression that can fail silently in some edited versions,
// normalize it to the local variable used by the guaranteed badge.
$contents = str_replace(
    '{{ $parcel->agricultural_status_label }}',
    '{{ $agriculturalStatusLabel ?? $parcel->agricultural_status_label }}',
    $contents
);

if ($contents !== $original) {
    file_put_contents($viewPath, $contents);
    echo "Updated {$viewRelative}\n";
} else {
    echo "No change {$viewRelative}\n";
}

echo "\nNow run:\n";
echo "php artisan view:clear\n";
echo "php artisan test --filter=ParcelAgriculturalStatusTest\n";
