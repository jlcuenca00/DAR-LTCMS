<?php

/**
 * DAR-iLAND legacy status/wording audit.
 *
 * This script only scans and reports. It does not modify files.
 * Use it before final cleanup so we do not accidentally remove compatibility
 * constants, route names, or historical tests that are still intentionally needed.
 */

$root = realpath(__DIR__ . '/..');

if (! $root) {
    fwrite(STDERR, "Could not resolve project root.\n");
    exit(1);
}

$scanDirs = [
    'app',
    'database',
    'resources/views',
    'routes',
    'tests',
];

$patterns = [
    'approved' => 'legacy approved status / wording',
    'not_approved' => 'legacy not_approved status / wording',
    'Not Approved' => 'legacy display wording',
    'not approved' => 'legacy display wording',
    'Released Clearance' => 'old display wording; Form No. 5 should show APPROVED when referring to official form decision',
    'Approved Clearance' => 'old notification/output wording',
    'application_approved' => 'old notification/audit event type',
    'application_not_approved' => 'old notification/audit event type',
    'pending_review' => 'legacy pending review status',
    'draft' => 'legacy draft status',
    'ownership mutation' => 'scope wording check',
    'registry mutation' => 'scope wording check',
];

$allowedHints = [
    'app/Models/LandTransferApplication.php' => [
        'STATUS_APPROVED',
        'STATUS_NOT_APPROVED',
        'LEGACY_FINAL_STATUSES',
        'STATUS_PENDING_REVIEW',
        'STATUS_DRAFT',
    ],
    'database/migrations' => [
        'normalize_application_status_values',
    ],
];

$results = [];

foreach ($scanDirs as $scanDir) {
    $basePath = $root . DIRECTORY_SEPARATOR . $scanDir;

    if (! is_dir($basePath)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $extension = strtolower($file->getExtension());

        if (! in_array($extension, ['php', 'blade.php', 'js', 'json', 'md', 'txt'], true)) {
            continue;
        }

        $path = $file->getPathname();
        $relativePath = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
        $content = file_get_contents($path);

        if ($content === false) {
            continue;
        }

        $lines = preg_split('/\R/', $content);

        foreach ($lines as $index => $line) {
            foreach ($patterns as $pattern => $description) {
                if (stripos($line, $pattern) === false) {
                    continue;
                }

                $isAllowed = false;

                foreach ($allowedHints as $allowedPath => $allowedNeedles) {
                    if (str_starts_with(str_replace('\\', '/', $relativePath), $allowedPath)) {
                        foreach ($allowedNeedles as $allowedNeedle) {
                            if (stripos($line, $allowedNeedle) !== false || stripos($relativePath, $allowedNeedle) !== false) {
                                $isAllowed = true;
                                break 2;
                            }
                        }
                    }
                }

                $results[] = [
                    'file' => str_replace('\\', '/', $relativePath),
                    'line' => $index + 1,
                    'pattern' => $pattern,
                    'description' => $description,
                    'allowed_hint' => $isAllowed,
                    'text' => trim($line),
                ];
            }
        }
    }
}

if (empty($results)) {
    echo "No legacy status/scope wording matches found.\n";
    exit(0);
}

echo "DAR-iLAND Legacy Status / Scope Wording Audit\n";
echo "=============================================\n\n";
echo "Total matches: " . count($results) . "\n\n";

$grouped = [];

foreach ($results as $result) {
    $grouped[$result['file']][] = $result;
}

foreach ($grouped as $file => $items) {
    echo $file . "\n";
    echo str_repeat('-', strlen($file)) . "\n";

    foreach ($items as $item) {
        $flag = $item['allowed_hint'] ? 'INTENTIONAL/REVIEW' : 'CHECK';
        echo "  [{$flag}] L{$item['line']} | {$item['pattern']} | {$item['description']}\n";
        echo "      " . mb_substr($item['text'], 0, 220) . "\n";
    }

    echo "\n";
}

echo "Next step:\n";
echo "- CHECK items are candidates for cleanup.\n";
echo "- INTENTIONAL/REVIEW items may be compatibility constants, normalization migrations, or tests.\n";
echo "- Do not remove legacy constants unless all old DB values have been normalized and tests confirm compatibility is no longer needed.\n";
