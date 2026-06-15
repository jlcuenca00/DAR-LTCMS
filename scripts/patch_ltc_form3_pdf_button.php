<?php

$path = __DIR__ . '/../resources/views/staff/applications/partials/acknowledgement-receipt.blade.php';

if (! file_exists($path)) {
    fwrite(STDERR, "Cannot find resources/views/staff/applications/partials/acknowledgement-receipt.blade.php\n");
    exit(1);
}

$content = file_get_contents($path);

if (str_contains($content, "staff.applications.acknowledgement.pdf")) {
    echo "LTC Form No. 3 PDF button already exists. No changes made.\n";
    exit(0);
}

$needle = <<<'BLADE'
        <button type="button" class="staff-button staff-button-light" onclick="window.print()">
            <i class="fa-solid fa-print"></i>
            Print Page
        </button>
BLADE;

$replacement = <<<'BLADE'
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
            <a href="{{ route('staff.applications.acknowledgement.pdf', $application) }}"
               class="staff-button staff-button-primary"
               target="_blank">
                <i class="fa-solid fa-file-pdf"></i>
                Open Form No. 3 PDF
            </a>

            <button type="button" class="staff-button staff-button-light" onclick="window.print()">
                <i class="fa-solid fa-print"></i>
                Print Page
            </button>
        </div>
BLADE;

$pos = strpos($content, $needle);

if ($pos === false) {
    fwrite(STDERR, "Could not find Print Page button. Add this link manually in the Form No. 3 header actions:\n");
    fwrite(STDERR, "<a href=\"{{ route('staff.applications.acknowledgement.pdf', \$application) }}\" class=\"staff-button staff-button-primary\" target=\"_blank\">Open Form No. 3 PDF</a>\n");
    exit(1);
}

$content = substr($content, 0, $pos)
    . $replacement
    . substr($content, $pos + strlen($needle));

file_put_contents($path, $content);

echo "Added LTC Form No. 3 PDF button to acknowledgement receipt partial.\n";
