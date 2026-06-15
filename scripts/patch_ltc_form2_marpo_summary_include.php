<?php

$showPath = __DIR__ . '/../resources/views/staff/applications/show.blade.php';

if (! file_exists($showPath)) {
    fwrite(STDERR, "Cannot find resources/views/staff/applications/show.blade.php\n");
    exit(1);
}

$content = file_get_contents($showPath);

if (str_contains($content, "staff.applications.partials.marpo-certification-summary")) {
    echo "MARPO Certification summary include already exists. No changes made.\n";
    exit(0);
}

$include = "        @include('staff.applications.partials.marpo-certification-summary')\n\n";

$anchors = [
    "        @include('staff.applications.partials.acknowledgement-receipt')\n\n",
    "        @include('staff.applications.partials.form4-attestation-recommendation')\n\n",
];

foreach ($anchors as $anchor) {
    $pos = strpos($content, $anchor);

    if ($pos !== false) {
        $content = substr($content, 0, $pos + strlen($anchor))
            . $include
            . substr($content, $pos + strlen($anchor));

        file_put_contents($showPath, $content);
        echo "Inserted MARPO Certification summary partial.\n";
        exit(0);
    }
}

$needle = <<<'BLADE'
        <section class="review-panel">
            <div class="review-panel-body completion-card">
BLADE;

$pos = strpos($content, $needle);

if ($pos === false) {
    fwrite(STDERR, "Could not find a safe insertion point. Add this manually in show.blade.php:\n");
    fwrite(STDERR, "@include('staff.applications.partials.marpo-certification-summary')\n");
    exit(1);
}

$content = substr($content, 0, $pos)
    . $include
    . substr($content, $pos);

file_put_contents($showPath, $content);

echo "Inserted MARPO Certification summary partial before checklist completion.\n";
