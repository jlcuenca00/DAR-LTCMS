<?php

$path = __DIR__ . '/../resources/views/staff/applications/partials/form4-attestation-recommendation.blade.php';

if (! file_exists($path)) {
    fwrite(STDERR, "Cannot find resources/views/staff/applications/partials/form4-attestation-recommendation.blade.php\n");
    exit(1);
}

$content = file_get_contents($path);

if (str_contains($content, "staff.applications.form4.pdf")) {
    echo "LTC Form No. 4 PDF button already exists. No changes made.\n";
    exit(0);
}

$needle = <<<'BLADE'
    <div class="review-panel-header">
        <div>
            <h2 class="review-panel-title">LTC Form No. 4 — Certification, Attestation and Recommendation</h2>
            <p class="review-panel-subtitle">
                Encode LTI/Legal review findings and recommendation details. This is decision-support context only;
                final release or denial remains subject to authorized review and does not transfer ownership.
            </p>
        </div>
    </div>
BLADE;

$replacement = <<<'BLADE'
    <div class="review-panel-header">
        <div>
            <h2 class="review-panel-title">LTC Form No. 4 — Certification, Attestation and Recommendation</h2>
            <p class="review-panel-subtitle">
                Encode LTI/Legal review findings and recommendation details. This is decision-support context only;
                final release or denial remains subject to authorized review and does not transfer ownership.
            </p>
        </div>

        <a href="{{ route('staff.applications.form4.pdf', $application) }}"
           class="staff-button staff-button-primary"
           target="_blank">
            <i class="fa-solid fa-file-pdf"></i>
            Open Form No. 4 PDF
        </a>
    </div>
BLADE;

$pos = strpos($content, $needle);

if ($pos === false) {
    fwrite(STDERR, "Could not find Form No. 4 header. Add this link manually inside the header actions:\n");
    fwrite(STDERR, "<a href=\"{{ route('staff.applications.form4.pdf', \$application) }}\" class=\"staff-button staff-button-primary\" target=\"_blank\">Open Form No. 4 PDF</a>\n");
    exit(1);
}

$content = substr($content, 0, $pos)
    . $replacement
    . substr($content, $pos + strlen($needle));

file_put_contents($path, $content);

echo "Added LTC Form No. 4 PDF button to staff review partial.\n";
