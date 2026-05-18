<?php

function path_to(string $relative): string
{
    return getcwd() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
}

function read_project_file(string $relative): ?string
{
    $path = path_to($relative);
    if (! file_exists($path)) {
        echo "SKIP missing: {$relative}\n";
        return null;
    }

    return file_get_contents($path);
}

function write_project_file(string $relative, string $contents): void
{
    file_put_contents(path_to($relative), $contents);
    echo "UPDATED: {$relative}\n";
}

function dropdown_block(bool $withId = true): string
{
    $id = $withId ? ' id="crop_or_land_use"' : '';

    return <<<'BLADE'
@php
    $agriculturalClassificationOptions = [
        'Private Agricultural Land',
        'Awarded CLOA Land',
        'Emancipation Patent Land',
        'CARP-Covered Land',
        'Not Yet Determined',
        'Non-Agricultural / Reference Only',
    ];
@endphp
<select__ID__ name="crop_or_land_use" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
    <option value="">Select classification</option>
    @foreach ($agriculturalClassificationOptions as $classification)
        <option value="{{ $classification }}" @selected(old('crop_or_land_use') === $classification)>{{ $classification }}</option>
    @endforeach
</select>
<p class="mt-1 text-xs text-gray-500">Use the classification indicated by the source document, if available.</p>
BLADE;
}

function normalize_source_wording(string $content): string
{
    return str_replace(
        [
            'Crop / Land Use',
            'Agricultural Classification Notes',
            'Area / Land Use',
            'No land use recorded',
            'No agricultural classification note recorded',
        ],
        [
            'Agricultural Classification',
            'Agricultural Classification',
            'Area / Agricultural Classification',
            'No agricultural classification recorded',
            'No agricultural classification recorded',
        ],
        $content
    );
}

$files = [
    'resources/views/staff/source-record-packages/create.blade.php',
    'resources/views/staff/legacy-records/create.blade.php',
    'resources/views/staff/source-record-packages/show.blade.php',
    'resources/views/staff/legacy-records/show.blade.php',
    'resources/views/staff/source-record-packages/import.blade.php',
    'resources/views/staff/source-record-packages/import-preview.blade.php',
];

foreach ($files as $relative) {
    $content = read_project_file($relative);
    if ($content === null) {
        continue;
    }

    $original = $content;
    $content = normalize_source_wording($content);

    if (str_ends_with($relative, 'source-record-packages/create.blade.php')) {
        if (! str_contains($content, '<select id="crop_or_land_use"')) {
            $select = str_replace('__ID__', ' id="crop_or_land_use"', dropdown_block(true));
            $content = preg_replace(
                '/<input\s+id="crop_or_land_use"\s+name="crop_or_land_use"[^>]*>/i',
                $select,
                $content,
                1
            );
        }
    }

    if (str_ends_with($relative, 'legacy-records/create.blade.php')) {
        if (! str_contains($content, '<select name="crop_or_land_use"') && ! str_contains($content, '<select id="crop_or_land_use"')) {
            $select = str_replace('__ID__', '', dropdown_block(false));
            $content = preg_replace(
                '/<input\s+name="crop_or_land_use"[^>]*>/i',
                $select,
                $content,
                1
            );
        }
    }

    if ($content !== $original) {
        write_project_file($relative, $content);
    } else {
        echo "OK unchanged: {$relative}\n";
    }
}

echo "\nDone. Run: php artisan view:clear && php artisan route:clear && php artisan test\n";
