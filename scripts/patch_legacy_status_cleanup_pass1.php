<?php

/**
 * DAR-iLAND legacy status cleanup pass 1.
 *
 * Safe cleanup only:
 * - Update demo seeders from pending_review to pending_legal_review.
 * - Rename visible CSS/status helper class names where they are only styling labels.
 * - Update obvious dashboard direct string comparisons.
 * - Do not remove compatibility constants.
 * - Do not edit historical migrations.
 * - Do not remove scope notices saying no ownership/registry mutation.
 */

function patchFile(string $path, callable $callback): void
{
    if (! file_exists($path)) {
        echo "Skipped missing file: {$path}\n";
        return;
    }

    $before = file_get_contents($path);
    $after = $callback($before);

    if ($after === $before) {
        echo "No changes: {$path}\n";
        return;
    }

    file_put_contents($path, $after);
    echo "Patched: {$path}\n";
}

$root = realpath(__DIR__ . '/..');

if (! $root) {
    fwrite(STDERR, "Could not resolve project root.\n");
    exit(1);
}

$path = fn (string $relative) => $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

// 1) Demo seeders: old workflow values should use revised office status.
patchFile($path('database/seeders/LandownerPrivacyDemoSeeder.php'), function (string $content): string {
    return str_replace(
        "'status' => 'pending_review',",
        "'status' => \\App\\Models\\LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,",
        $content
    );
});

patchFile($path('database/seeders/ParcelMapDemoSeeder.php'), function (string $content): string {
    return str_replace(
        "'status' => 'pending_review',",
        "'status' => \\App\\Models\\LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,",
        $content
    );
});

// 2) Dashboard view CSS class names: these were generic UI badges, not official form decisions.
patchFile($path('resources/views/dashboards/geodetic.blade.php'), function (string $content): string {
    $content = str_replace('.geo-badge-approved', '.geo-badge-has-geometry', $content);
    $content = str_replace('.geo-badge-draft', '.geo-badge-needs-geometry', $content);
    $content = str_replace('geo-badge-approved', 'geo-badge-has-geometry', $content);
    $content = str_replace('geo-badge-draft', 'geo-badge-needs-geometry', $content);

    return $content;
});

patchFile($path('resources/views/dashboards/staff.blade.php'), function (string $content): string {
    $content = str_replace('.status-approved', '.status-released', $content);
    $content = str_replace('.status-not_approved', '.status-denied', $content);
    $content = str_replace('.status-pending_review', '.status-pending-legal-review', $content);
    $content = str_replace('.status-draft', '.status-pending-legal-review', $content);

    $content = str_replace("\$application->status === 'approved'", "\$application->status === 'released'", $content);
    $content = str_replace("\$application->status === 'not_approved'", "\$application->status === 'denied'", $content);
    $content = str_replace("\$application->status === 'pending_review'", "\$application->status === 'pending_legal_review'", $content);
    $content = str_replace("\$application->status === 'draft'", "\$application->status === 'pending_legal_review'", $content);

    return $content;
});

// 3) Map JS status color fallback: remove old pending_review check, keep only revised status.
foreach ([
    'resources/views/geodetic/maps/parcel-map.blade.php',
    'resources/views/landowner/maps/parcel-map.blade.php',
    'resources/views/staff/maps/parcel-map.blade.php',
] as $relative) {
    patchFile($path($relative), function (string $content): string {
        $content = str_replace("status === 'pending_review'", "status === 'pending_legal_review'", $content);
        $content = str_replace('status === "pending_review"', 'status === "pending_legal_review"', $content);

        return $content;
    });
}

// 4) Source package status dropdown: source package statuses should not use old application workflow value.
patchFile($path('resources/views/staff/source-record-packages/show.blade.php'), function (string $content): string {
    $content = str_replace(
        '<option value="pending_review" @selected(old(\'status\') === \'pending_review\')>Pending Review</option>',
        '<option value="pending_legal_review" @selected(old(\'status\') === \'pending_legal_review\')>Pending Review by Legal Officer</option>',
        $content
    );

    return $content;
});

// 5) Staff application review visible clearance badge wording.
// Keep route name not_approved for compatibility; only visible label is adjusted.
patchFile($path('resources/views/staff/applications/show.blade.php'), function (string $content): string {
    $content = str_replace(
        "'released', 'approved' => 'staff-badge-green',",
        "'released' => 'staff-badge-green',",
        $content
    );

    $content = str_replace(
        "'denied', 'not_approved' => 'staff-badge-red',",
        "'denied' => 'staff-badge-red',",
        $content
    );

    $content = str_replace(
        "'pending_legal_review', 'endorsed_lti', 'endorsed_chief_legal', 'endorsed_parpo', 'for_releasing', 'pending_review' => 'staff-badge-amber',",
        "'pending_legal_review', 'endorsed_lti', 'endorsed_chief_legal', 'endorsed_parpo', 'for_releasing' => 'staff-badge-amber',",
        $content
    );

    $content = str_replace(
        "'draft' => 'staff-badge-slate',",
        "",
        $content
    );

    $content = str_replace(
        "{{ in_array(\$application->clearance->decision_status, ['released', 'approved'], true) ? 'Clearance Released' : 'Denied' }}",
        "{{ \$application->clearance->decision_status === 'released' ? 'APPROVED' : 'DENIED' }}",
        $content
    );

    $content = str_replace(
        "in_array(\$application->clearance->decision_status, ['released', 'approved'], true)",
        "\$application->clearance->decision_status === 'released'",
        $content
    );

    $content = str_replace(
        "copy: 'This will generate and record a released clearance result for this application.'",
        "copy: 'This will generate and record the approved LTC Form No. 5 result for this application.'",
        $content
    );

    return $content;
});

// 6) Monitoring report labels: use system status wording, not “Released Clearance”.
patchFile($path('resources/views/staff/reports/monitoring.blade.php'), function (string $content): string {
    $content = str_replace(
        "'released', 'approved' => 'Released Clearance',",
        "'released' => 'Released',",
        $content
    );

    $content = str_replace(
        "'denied', 'not_approved' => 'Denied',",
        "'denied' => 'Denied',",
        $content
    );

    return $content;
});

patchFile($path('resources/views/staff/reports/monitoring-print.blade.php'), function (string $content): string {
    $content = str_replace(
        "'approved', 'released' => 'Released Clearance',",
        "'released' => 'Released',",
        $content
    );

    $content = str_replace(
        "'approved', 'released' => 'Released',",
        "'released' => 'Released',",
        $content
    );

    $content = str_replace(
        "'not_approved', 'denied' => 'Denied',",
        "'denied' => 'Denied',",
        $content
    );

    return $content;
});

echo "\nPass 1 cleanup complete.\n";
echo "Leftover audit matches after this are expected in compatibility constants, old migrations, tests, route names, and required scope notices.\n";
