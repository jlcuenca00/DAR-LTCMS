<?php

/**
 * DAR-iLAND legacy status cleanup pass 3.
 *
 * Safe target:
 * - Staff application index visible status badge map.
 *
 * Does not edit:
 * - backend compatibility constants
 * - old migrations
 * - tests
 * - route names
 * - audit/scope notices
 */

$path = __DIR__ . '/../resources/views/staff/applications/index.blade.php';

if (! file_exists($path)) {
    fwrite(STDERR, "Cannot find resources/views/staff/applications/index.blade.php\n");
    exit(1);
}

$content = file_get_contents($path);

$replacements = [
    "        \\App\\Models\\LandTransferApplication::STATUS_APPROVED => 'staff-badge-green',\n" => "",
    "        \\App\\Models\\LandTransferApplication::STATUS_NOT_APPROVED => 'staff-badge-red',\n" => "",
    "        \\App\\Models\\LandTransferApplication::STATUS_PENDING_REVIEW => 'staff-badge-amber',\n" => "",
];

foreach ($replacements as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

// If this file still has direct old strings in a match expression, clean only display mappings.
$content = str_replace(
    "        'approved' => 'staff-badge-green',\n",
    "",
    $content
);

$content = str_replace(
    "        'not_approved' => 'staff-badge-red',\n",
    "",
    $content
);

$content = str_replace(
    "        'pending_review' => 'staff-badge-amber',\n",
    "",
    $content
);

file_put_contents($path, $content);

echo "Patched staff application index visible legacy status badge mappings.\n";
