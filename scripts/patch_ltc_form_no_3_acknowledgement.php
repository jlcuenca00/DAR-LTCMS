<?php

$showPath = __DIR__ . '/../resources/views/staff/applications/show.blade.php';

if (! file_exists($showPath)) {
    fwrite(STDERR, "Cannot find resources/views/staff/applications/show.blade.php\n");
    exit(1);
}

$content = file_get_contents($showPath);

$include = "        @include('staff.applications.partials.acknowledgement-receipt')\n\n";

if (str_contains($content, "staff.applications.partials.acknowledgement-receipt")) {
    echo "LTC Form No. 3 acknowledgement include already exists. No changes made.\n";
    exit(0);
}

$needle = <<<'BLADE'
        <section class="review-panel">
            <div class="review-panel-body completion-card">
                <div>
                    <h2 class="review-panel-title">Checklist Completion</h2>
                    <p class="review-panel-subtitle">
                        {{ $blockingUploadedCount }} / {{ $blockingTotal }} required acceptance documents uploaded. Case-dependent and reference-only documents remain visible for manual review.
                    </p>
                </div>
                <div class="completion-number">{{ $blockingUploadedCount }} / {{ $blockingTotal }}</div>
            </div>
        </section>

BLADE;

if (! str_contains($content, $needle)) {
    fwrite(STDERR, "Could not find the Checklist Completion section. Please insert this line manually after that section:\n\n");
    fwrite(STDERR, "@include('staff.applications.partials.acknowledgement-receipt')\n");
    exit(1);
}

$content = str_replace($needle, $needle . $include, $content);

file_put_contents($showPath, $content);

echo "Inserted LTC Form No. 3 acknowledgement receipt partial into staff application show page.\n";
