<?php

$showPath = __DIR__ . '/../resources/views/staff/applications/show.blade.php';

if (! file_exists($showPath)) {
    fwrite(STDERR, "Cannot find resources/views/staff/applications/show.blade.php\n");
    exit(1);
}

$content = file_get_contents($showPath);

if (str_contains($content, "staff.applications.partials.form4-attestation-recommendation")) {
    echo "LTC Form No. 4 include already exists. No changes made.\n";
    exit(0);
}

$include = "        @include('staff.applications.partials.form4-attestation-recommendation')\n\n";

if (str_contains($content, "staff.applications.partials.acknowledgement-receipt")) {
    $needle = "        @include('staff.applications.partials.acknowledgement-receipt')\n\n";
    $content = str_replace($needle, $needle . $include, $content);
    file_put_contents($showPath, $content);
    echo "Inserted LTC Form No. 4 partial after LTC Form No. 3 acknowledgement.\n";
    exit(0);
}

$needle = <<<'BLADE'
        <section class="review-panel">
            <div class="review-panel-body completion-card">
                <div>
                    <h2 class="review-panel-title">Checklist Completion</h2>
BLADE;

$pos = strpos($content, $needle);
if ($pos === false) {
    fwrite(STDERR, "Could not find insertion point. Add this manually in show.blade.php:\n@include('staff.applications.partials.form4-attestation-recommendation')\n");
    exit(1);
}

$content = substr($content, 0, $pos) . $include . substr($content, $pos);
file_put_contents($showPath, $content);

echo "Inserted LTC Form No. 4 partial before checklist completion.\n";
