<?php

/*
 | DAR-LTCMS patch helper
 | Fixes agricultural_status behavior after removing the visible land type field from parcel forms.
 |
 | Fixes:
 | 1) Staff parcel update defaults missing agricultural_status to private_agricultural.
 | 2) Staff parcel index actually applies the agricultural_status filter.
 |
 | Scope-safe: this is classification/monitoring only. No approval gate, no registry mutation,
 | no ownership transfer behavior.
 */

$root = getcwd();
$relative = 'app/Http/Controllers/Staff/RecordSearchController.php';
$path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

if (! is_file($path)) {
    fwrite(STDERR, "ERROR: {$relative} not found. Run this from your Laravel project root.\n");
    exit(1);
}

$controller = file_get_contents($path);
$original = $controller;

function find_function_span(string $code, string $functionName): ?array
{
    $needle = 'public function ' . $functionName;
    $start = strpos($code, $needle);

    if ($start === false) {
        return null;
    }

    $brace = strpos($code, '{', $start);

    if ($brace === false) {
        return null;
    }

    $depth = 0;
    $length = strlen($code);

    for ($i = $brace; $i < $length; $i++) {
        $char = $code[$i];

        if ($char === '{') {
            $depth++;
        }

        if ($char === '}') {
            $depth--;

            if ($depth === 0) {
                return [$start, $i + 1];
            }
        }
    }

    return null;
}

function replace_function_body(string $code, string $functionName, callable $callback): string
{
    $span = find_function_span($code, $functionName);

    if ($span === null) {
        return $code;
    }

    [$start, $end] = $span;
    $function = substr($code, $start, $end - $start);
    $newFunction = $callback($function);

    return substr($code, 0, $start) . $newFunction . substr($code, $end);
}

// Ensure the parcels index accepts and applies agricultural_status.
$controller = replace_function_body($controller, 'parcels', function (string $function): string {
    // Add validation rule inside the parcels filter validation array only.
    if (! str_contains($function, "'agricultural_status'")) {
        $function = preg_replace(
            "/('status'\s*=>\s*\[[^\n;]+\],)/",
            "$1\n            'agricultural_status' => ['nullable', 'string', Rule::in(array_keys(Parcel::agriculturalStatusOptions()))],",
            $function,
            1
        );
    }

    // Apply the actual query filter before pagination. The view already showed the dropdown,
    // but the query still returned all parcels.
    if (! str_contains($function, 'automatic agricultural status index filter')) {
        $filterBlock = <<<'PHP_CODE'

        if (($filters['agricultural_status'] ?? null) !== null && $filters['agricultural_status'] !== '') {
            $parcelsQuery->where('agricultural_status', $filters['agricultural_status']); // automatic agricultural status index filter
        }
PHP_CODE;

        $function = str_replace(
            "\n        $" . "parcels = $" . "parcelsQuery",
            $filterBlock . "\n\n        $" . "parcels = $" . "parcelsQuery",
            $function
        );
    }

    // Make sure the view still receives status options if not already patched.
    if (! str_contains($function, '$agriculturalStatuses = Parcel::agriculturalStatusOptions();')) {
        $function = str_replace(
            "\n\n        return view('staff.records.parcels', compact(",
            "\n\n        $" . "agriculturalStatuses = Parcel::agriculturalStatusOptions();\n\n        return view('staff.records.parcels', compact(",
            $function
        );
    }

    if (str_contains($function, "'statuses'\n        ));") && ! str_contains($function, "'agriculturalStatuses'")) {
        $function = str_replace(
            "            'statuses'\n        ));",
            "            'statuses',\n            'agriculturalStatuses'\n        ));",
            $function
        );
    }

    return $function;
});

// Ensure updateParcel defaults the internal field after the form stops submitting it.
$controller = replace_function_body($controller, 'updateParcel', function (string $function): string {
    // The field was removed from the edit form, so validation should not require it.
    $function = preg_replace(
        "/\n\s*'agricultural_status'\s*=>\s*\[[^\n]+\],/",
        '',
        $function
    );

    if (! str_contains($function, 'automatic agricultural default after form removal')) {
        if (str_contains($function, '$data = $request->validate')) {
            $function = preg_replace(
                '/(\$data\s*=\s*\$request->validate\s*\([^;]+;)/s',
                "$1\n\n        $" . "data['agricultural_status'] = $" . "data['agricultural_status'] ?? 'private_agricultural'; // automatic agricultural default after form removal",
                $function,
                1
            );
        } elseif (str_contains($function, '$validated = $request->validate')) {
            $function = preg_replace(
                '/(\$validated\s*=\s*\$request->validate\s*\([^;]+;)/s',
                "$1\n\n        $" . "validated['agricultural_status'] = $" . "validated['agricultural_status'] ?? 'private_agricultural'; // automatic agricultural default after form removal",
                $function,
                1
            );
        }
    }

    return $function;
});

if ($controller === $original) {
    echo "No changes made to {$relative}. It may already be fixed, or the controller structure differs.\n";
} else {
    file_put_contents($path, $controller);
    echo "Updated {$relative}\n";
}

echo "\nNow run:\n";
echo "php artisan view:clear\n";
echo "php artisan route:clear\n";
echo "php artisan test --filter=ParcelAgriculturalStatusTest\n";
echo "php artisan test\n";
