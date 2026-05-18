<?php

/*
 | DAR-LTCMS patch helper
 | Removes Agricultural Status / land type selection from staff parcel forms,
 | and makes new parcel saves default to private_agricultural when the field is absent.
 |
 | Scope-safe: this does not rename Land Transfer Clearance, does not add any workflow gate,
 | and does not mutate ownership/registry records.
 */

$root = getcwd();

function read_file_or_null(string $path): ?string
{
    return is_file($path) ? file_get_contents($path) : null;
}

function write_if_changed(string $path, string $new): bool
{
    $old = is_file($path) ? file_get_contents($path) : null;
    if ($old === $new) {
        return false;
    }
    file_put_contents($path, $new);
    return true;
}

function remove_agricultural_status_form_blocks(string $content): string
{
    // Remove common wrapped field blocks containing agricultural_status select/input.
    $patterns = [
        // Whole div containing label + agricultural_status field, including helper text.
        '/\n\s*<div[^>]*>\s*\n\s*<label[^>]*>\s*Agricultural Status\s*<\/label>.*?name=["\']agricultural_status["\'].*?<\/div>\s*/is',
        '/\n\s*<div[^>]*>\s*\n\s*<x-input-label[^>]*:value=["\']__(\(["\']Agricultural Status["\']\)|["\']Agricultural Status["\'])[^>]*>?.*?name=["\']agricultural_status["\'].*?<\/div>\s*/is',
        '/\n\s*<div[^>]*>.*?<label[^>]*for=["\']agricultural_status["\'][^>]*>.*?<\/label>.*?<select[^>]*name=["\']agricultural_status["\'].*?<\/select>.*?<\/div>\s*/is',
        '/\n\s*<div[^>]*>.*?<select[^>]*name=["\']agricultural_status["\'].*?<\/select>.*?<\/div>\s*/is',
        // Fallback: remove lone select plus immediately following helper paragraph.
        '/\n\s*<select[^>]*name=["\']agricultural_status["\'].*?<\/select>\s*(<p[^>]*>.*?<\/p>)?/is',
        // Fallback for input type hidden/normal accidentally left behind.
        '/\n\s*<input[^>]*name=["\']agricultural_status["\'][^>]*>\s*/is',
    ];

    foreach ($patterns as $pattern) {
        $content = preg_replace($pattern, "\n", $content);
    }

    // Remove nearby helper text if left orphaned.
    $content = preg_replace('/\n\s*<p[^>]*>\s*Used for DAR record classification and monitoring\. This does not transfer ownership or mutate registry records\.\s*<\/p>\s*/i', "\n", $content);

    return $content;
}

$changed = [];

// 1) Remove the visible field from known staff parcel form files.
$formCandidates = [
    'resources/views/staff/records/parcel-create.blade.php',
    'resources/views/staff/records/parcel-edit.blade.php',
    'resources/views/staff/records/parcels/create.blade.php',
    'resources/views/staff/records/parcels/edit.blade.php',
    'resources/views/staff/parcels/create.blade.php',
    'resources/views/staff/parcels/edit.blade.php',
];

foreach ($formCandidates as $relative) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $content = read_file_or_null($path);
    if ($content === null) {
        continue;
    }

    $new = remove_agricultural_status_form_blocks($content);
    if (write_if_changed($path, $new)) {
        $changed[] = $relative;
    }
}

// 2) Also scan staff view form files for agricultural_status, but avoid show/index/report pages.
$viewsRoot = $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'staff';
if (is_dir($viewsRoot)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsRoot, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $path = $file->getPathname();
        $norm = str_replace('\\', '/', substr($path, strlen($root) + 1));
        if (!str_ends_with($norm, '.blade.php')) continue;
        if (!str_contains($norm, 'create') && !str_contains($norm, 'edit') && !str_contains($norm, 'form')) continue;

        $content = file_get_contents($path);
        if (!str_contains($content, 'agricultural_status') && !str_contains($content, 'Agricultural Status')) continue;

        $new = remove_agricultural_status_form_blocks($content);
        if (write_if_changed($path, $new)) {
            $changed[] = $norm;
        }
    }
}

// 3) Make controller saves default to private_agricultural when no form value is submitted.
// Conservative: only patch files that already mention agricultural_status and Parcel create/update logic.
$controllersRoot = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers';
if (is_dir($controllersRoot)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllersRoot, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $path = $file->getPathname();
        if (!str_ends_with($path, '.php')) continue;
        $content = file_get_contents($path);
        if (!str_contains($content, 'agricultural_status')) continue;
        if (!str_contains($content, 'Parcel')) continue;

        $new = $content;

        // Remove agricultural_status validation requirement if it exists; it should no longer be submitted from forms.
        $new = preg_replace("/\n\s*['\"]agricultural_status['\"]\s*=>\s*[^\n]+,?\n/", "\n", $new);

        // After common validated data assignments, inject default only once per file.
        if (!str_contains($new, "private_agricultural'; // default agricultural status")) {
            $injection = "\n        $" . "validated['agricultural_status'] = $" . "validated['agricultural_status'] ?? 'private_agricultural'; // default agricultural status\n";

            $new = preg_replace(
                '/(\$validated\s*=\s*\$request->validate\s*\([^;]+;)/s',
                '$1' . $injection,
                $new,
                1
            );
        }

        if (write_if_changed($path, $new)) {
            $changed[] = str_replace('\\', '/', substr($path, strlen($root) + 1));
        }
    }
}

// 4) Ensure model default helper exists if model has casts/boot space; keep non-invasive.
$modelPath = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'Parcel.php';
$model = read_file_or_null($modelPath);
if ($model !== null && !str_contains($model, "DEFAULT_AGRICULTURAL_STATUS")) {
    $model = preg_replace(
        '/class\s+Parcel\s+extends\s+Model\s*\{/',
        "class Parcel extends Model\n{\n    public const DEFAULT_AGRICULTURAL_STATUS = 'private_agricultural';",
        $model,
        1
    );
    if (write_if_changed($modelPath, $model)) {
        $changed[] = 'app/Models/Parcel.php';
    }
}

echo "\nRemoved visible land type/agricultural status controls from parcel forms.\n";
echo "New parcel saves should default to private_agricultural when the field is absent.\n\n";
echo "Changed files:\n";
foreach (array_values(array_unique($changed)) as $file) {
    echo "- {$file}\n";
}
if (empty($changed)) {
    echo "- No files changed. The field may already be removed or file names differ.\n";
}
echo "\nRun: php artisan view:clear && php artisan route:clear && php artisan test\n";
