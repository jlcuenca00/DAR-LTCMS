<?php

$root = getcwd();

function p(string $relative): string
{
    global $root;
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function write_if_changed(string $relative, string $content): void
{
    $path = p($relative);
    $old = is_file($path) ? file_get_contents($path) : null;
    if ($old !== $content) {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $content);
        echo "Updated {$relative}\n";
    } else {
        echo "No change {$relative}\n";
    }
}

$controllerPath = p('app/Http/Controllers/Staff/RecordSearchController.php');
if (! is_file($controllerPath)) {
    echo "RecordSearchController not found; skipped controller filter check.\n";
    exit(0);
}

$controller = file_get_contents($controllerPath);
$original = $controller;

if (! str_contains($controller, 'where(\'agricultural_status\'') && ! str_contains($controller, 'where("agricultural_status"')) {
    $needle = "        if (! empty($" . "filters['status'])) {\n            $" . "parcelsQuery->where('status', $" . "filters['status']);\n        }";
    $insert = $needle . "\n\n        if (! empty($" . "filters['agricultural_status'])) {\n            $" . "parcelsQuery->where('agricultural_status', $" . "filters['agricultural_status']);\n        }";
    if (str_contains($controller, $needle)) {
        $controller = str_replace($needle, $insert, $controller);
    }
}

if (! str_contains($controller, '$agriculturalStatuses = Parcel::agriculturalStatusOptions();')) {
    $needle = "        $" . "statuses = Parcel::query()\n            ->whereNotNull('status')\n            ->select('status')\n            ->distinct()\n            ->orderBy('status')\n            ->pluck('status');";
    $insert = $needle . "\n\n        $" . "agriculturalStatuses = Parcel::agriculturalStatusOptions();";
    if (str_contains($controller, $needle)) {
        $controller = str_replace($needle, $insert, $controller);
    }
}

if (! str_contains($controller, "'agriculturalStatuses'")) {
    $controller = str_replace(
        "            'statuses'\n        ));",
        "            'statuses',\n            'agriculturalStatuses'\n        ));",
        $controller
    );
}

// The edit form no longer submits agricultural_status, so update requests should internally default to private agricultural.
if (str_contains($controller, 'public function updateParcel') && ! str_contains($controller, "private_agricultural'; // automatic agricultural default")) {
    $controller = preg_replace(
        "/(\$data\s*=\s*\$request->validate\s*\([^;]+;)/s",
        "$1\n\n        $" . "data['agricultural_status'] = $" . "data['agricultural_status'] ?? 'private_agricultural'; // automatic agricultural default",
        $controller,
        1
    );
}

if ($controller !== $original) {
    file_put_contents($controllerPath, $controller);
    echo "Updated app/Http/Controllers/Staff/RecordSearchController.php\n";
} else {
    echo "No change app/Http/Controllers/Staff/RecordSearchController.php\n";
}

echo "Agricultural status form-removal follow-up complete.\n";
