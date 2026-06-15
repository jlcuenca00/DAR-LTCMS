<?php

$path = __DIR__ . '/../app/Http/Controllers/Staff/ApplicationClearanceController.php';

if (! file_exists($path)) {
    fwrite(STDERR, "Cannot find app/Http/Controllers/Staff/ApplicationClearanceController.php\n");
    exit(1);
}

$content = file_get_contents($path);

if (str_contains($content, "public function form4Pdf(")) {
    echo "LTC Form No. 4 PDF method already exists. No changes made.\n";
    exit(0);
}

$method = <<<'PHP'

    public function form4Pdf(LandTransferApplication $application)
    {
        $application->load([
            'applicationParcels.parcel',
            'transferorLandowner',
            'transfereeLandowner',
        ]);

        $pdf = Pdf::loadView('staff.applications.pdfs.form4-attestation-recommendation', [
            'application' => $application,
        ])->setPaper('a4');

        $safeApplicationCode = str_replace(['/', '\\', ' '], '-', (string) $application->application_code);

        return $pdf->stream('LTC-Form-No-4-' . $safeApplicationCode . '.pdf');
    }
PHP;

$lastBrace = strrpos($content, "\n}");
if ($lastBrace === false) {
    fwrite(STDERR, "Could not find final class closing brace.\n");
    exit(1);
}

$content = substr_replace($content, $method . "\n", $lastBrace, 0);

file_put_contents($path, $content);

echo "Added LTC Form No. 4 PDF method to ApplicationClearanceController.\n";
