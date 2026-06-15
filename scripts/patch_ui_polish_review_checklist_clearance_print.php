<?php

/**
 * DAR-iLAND UI polish patch:
 * - detailed application document checklist summary
 * - Quick Actions label cleanup
 * - Amount Paid label to PHP wording
 * - clearance print buttons point to updated Form No. 5 PDF
 * - reduce redundant scope reminder cards on clearance preview pages while keeping final PDF/footer scope notice
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

/**
 * 1) Staff application show: detailed checklist above requirement cards.
 */
patchFile($path('resources/views/staff/applications/show.blade.php'), function (string $content): string {
    if (! str_contains($content, '$checklistUploadedDocuments')) {
        $needle = <<<'BLADE'
        $totalReq = $transferorRequirements->count() + $transfereeRequirements->count();
        $uploadedCount = $uploaded->count();

BLADE;

        $replacement = <<<'BLADE'
        $totalReq = $transferorRequirements->count() + $transfereeRequirements->count();
        $uploadedCount = $uploaded->count();

        $allChecklistRequirements = $transferorRequirements->concat($transfereeRequirements);

        $checklistUploadedDocuments = $allChecklistRequirements
            ->filter(fn ($req) => $uploaded->has($req->id))
            ->map(fn ($req) => [
                'name' => $req->name,
                'party' => $req->party ?? null,
                'classification' => $req->requirement_classification ?? null,
                'file_attached' => filled(optional($uploaded->get($req->id))->file_path),
            ])
            ->values();

        $checklistMissingDocuments = $allChecklistRequirements
            ->reject(fn ($req) => $uploaded->has($req->id) && filled(optional($uploaded->get($req->id))->file_path))
            ->map(fn ($req) => [
                'name' => $req->name,
                'party' => $req->party ?? null,
                'classification' => $req->requirement_classification ?? null,
                'blocks_acceptance' => (bool) ($req->blocks_acceptance ?? false),
            ])
            ->values();

BLADE;

        if (str_contains($content, $needle)) {
            $content = str_replace($needle, $replacement, $content);
        }
    }

    if (! str_contains($content, 'Detailed Document Checklist')) {
        $needle = <<<'BLADE'
                <div class="completion-number">{{ $uploadedCount }} / {{ $totalReq }}</div>
            </div>
        </section>

BLADE;

        $replacement = <<<'BLADE'
                <div class="completion-number">{{ $uploadedCount }} / {{ $totalReq }}</div>

                <div style="grid-column:1 / -1; display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:12px; margin-top:14px;">
                    <div style="border:1px solid #bbf7d0; background:#f0fdf4; border-radius:12px; padding:14px;">
                        <h3 style="margin:0 0 8px; font-size:13px; font-weight:900; color:#166534;">Detailed Document Checklist — Checked / Uploaded</h3>

                        @if ($checklistUploadedDocuments->isNotEmpty())
                            <ul style="margin:0; padding-left:18px; font-size:12.5px; line-height:1.55; color:#14532d;">
                                @foreach ($checklistUploadedDocuments as $item)
                                    <li>
                                        <strong>{{ $item['name'] }}</strong>
                                        @if ($item['party'])
                                            <span>({{ ucfirst($item['party']) }})</span>
                                        @endif
                                        <span>— {{ $item['file_attached'] ? 'file attached' : 'metadata saved, no file attached' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p style="margin:0; font-size:12.5px; color:#166534;">No checklist items have uploaded files yet.</p>
                        @endif
                    </div>

                    <div style="border:1px solid #fecaca; background:#fef2f2; border-radius:12px; padding:14px;">
                        <h3 style="margin:0 0 8px; font-size:13px; font-weight:900; color:#991b1b;">Still Missing File / Needs Attention</h3>

                        @if ($checklistMissingDocuments->isNotEmpty())
                            <ul style="margin:0; padding-left:18px; font-size:12.5px; line-height:1.55; color:#7f1d1d;">
                                @foreach ($checklistMissingDocuments as $item)
                                    <li>
                                        <strong>{{ $item['name'] }}</strong>
                                        @if ($item['party'])
                                            <span>({{ ucfirst($item['party']) }})</span>
                                        @endif
                                        @if ($item['classification'])
                                            <span>— {{ ucwords(str_replace('_', ' ', $item['classification'])) }}</span>
                                        @endif
                                        @if ($item['blocks_acceptance'])
                                            <span style="font-weight:900;"> / blocks release</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p style="margin:0; font-size:12.5px; color:#166534;">All checklist items have uploaded files.</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>

BLADE;

        if (str_contains($content, $needle)) {
            $content = str_replace($needle, $replacement, $content);
        }
    }

    // Small wording polish on release button text.
    $content = str_replace(
        'Generate and record the released clearance result. This does not automatically transfer land ownership or mutate registry records.',
        'Generate and record the approved LTC Form No. 5 result. This does not automatically transfer land ownership.',
        $content
    );

    return $content;
});

/**
 * 2) Staff dashboard quick action label.
 */
patchFile($path('resources/views/dashboards/staff.blade.php'), function (string $content): string {
    $content = str_replace(
        '<p class="quick-title">Legal Review</p>',
        '<p class="quick-title">Applications for Review</p>',
        $content
    );

    $content = str_replace(
        '<p class="quick-desc">Open applications pending Legal Officer review.</p>',
        '<p class="quick-desc">Open applications that need staff review action.</p>',
        $content
    );

    return $content;
});

/**
 * 3) Amount Paid label to PHP wording across common views.
 */
foreach ([
    'resources/views/staff/applications/create.blade.php',
    'resources/views/staff/applications/show.blade.php',
    'resources/views/staff/clearances/pdf.blade.php',
    'resources/views/staff/clearances/show.blade.php',
    'resources/views/landowner/clearances/show.blade.php',
] as $relative) {
    patchFile($path($relative), function (string $content): string {
        $content = str_replace('Amount Paid</', 'Amount Paid (PHP)</', $content);
        $content = str_replace('Amount Paid:', 'Amount Paid (PHP):', $content);
        $content = str_replace('Amount Paid</span>', 'Amount Paid (PHP)</span>', $content);
        $content = str_replace('Amount Paid</td>', 'Amount Paid (PHP)</td>', $content);
        $content = str_replace('Amount Paid</label>', 'Amount Paid (PHP)</label>', $content);
        $content = str_replace('amount paid', 'amount paid in PHP', $content);

        return $content;
    });
}

/**
 * 4) Clearance preview print buttons should use updated Form No. 5 PDF, not stale browser print.
 */
patchFile($path('resources/views/staff/clearances/show.blade.php'), function (string $content): string {
    $content = str_replace(
        '<a href="{{ route(\'staff.applications.clearance.pdf\', $application) }}" class="btn primary" target="_blank">Open PDF Output</a>',
        '<a href="{{ route(\'staff.applications.clearance.pdf\', $application) }}" class="btn primary" target="_blank">Open Updated LTC Form No. 5 PDF</a>',
        $content
    );

    $content = str_replace(
        '<button type="button" onclick="window.print()" class="btn dark">Print / Save as PDF</button>',
        '<a href="{{ route(\'staff.applications.clearance.pdf\', $application) }}" class="btn dark" target="_blank">Print Updated LTC Form No. 5</a>',
        $content
    );

    // Reduce duplicate reminder card on preview page; footer/PDF still keep the scope limitation.
    $content = preg_replace(
        '/\s*<div class="scope-notice">\s*<strong>Scope Notice:<\/strong>.*?<\/div>\s*/s',
        "\n",
        $content,
        1
    );

    return $content;
});

patchFile($path('resources/views/landowner/clearances/show.blade.php'), function (string $content): string {
    $content = str_replace(
        '<a href="{{ route(\'landowner.applications.clearance.pdf\', $application) }}" class="btn primary" target="_blank">Open PDF Output</a>',
        '<a href="{{ route(\'landowner.applications.clearance.pdf\', $application) }}" class="btn primary" target="_blank">Open Updated LTC Form No. 5 PDF</a>',
        $content
    );

    $content = str_replace(
        '<button type="button" onclick="window.print()" class="btn dark">Print / Save as PDF</button>',
        '<a href="{{ route(\'landowner.applications.clearance.pdf\', $application) }}" class="btn dark" target="_blank">Print Updated LTC Form No. 5</a>',
        $content
    );

    // Reduce duplicate reminder card on preview page; footer/PDF still keep the scope limitation.
    $content = preg_replace(
        '/\s*<div class="scope-notice">\s*<strong>Scope Notice:<\/strong>.*?<\/div>\s*/s',
        "\n",
        $content,
        1
    );

    return $content;
});

echo "\nUI polish patch complete.\n";
echo "Scope reminders were reduced only on duplicate preview cards. Final PDF/footer scope notices remain.\n";
