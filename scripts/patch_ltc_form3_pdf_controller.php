<?php

$path = __DIR__ . '/../app/Http/Controllers/Staff/ApplicationClearanceController.php';

if (! file_exists($path)) {
    fwrite(STDERR, "Cannot find app/Http/Controllers/Staff/ApplicationClearanceController.php\n");
    exit(1);
}

$content = file_get_contents($path);

$imports = [
    "use App\\Models\\ApplicationDocument;\n",
    "use App\\Models\\RequiredDocument;\n",
];

foreach ($imports as $import) {
    if (! str_contains($content, $import)) {
        $content = preg_replace('/use App\\\\Models\\\\LandTransferApplication;\n/', "use App\\Models\\LandTransferApplication;\n" . $import, $content, 1);
    }
}

if (str_contains($content, "public function acknowledgementPdf(")) {
    file_put_contents($path, $content);
    echo "Acknowledgement PDF method already exists. Imports verified.\n";
    exit(0);
}

$method = <<<'PHP'

    public function acknowledgementPdf(LandTransferApplication $application)
    {
        $application->load([
            'documents.requiredDocument',
            'transferorLandowner',
            'transfereeLandowner',
        ]);

        $transferorRequirements = RequiredDocument::where('applies_to', 'transferor')
            ->orderBy('blocks_acceptance', 'desc')
            ->orderBy('requirement_classification')
            ->orderBy('name')
            ->get();

        $transfereeRequirements = RequiredDocument::where('applies_to', 'transferee')
            ->orderBy('blocks_acceptance', 'desc')
            ->orderBy('requirement_classification')
            ->orderBy('name')
            ->get();

        $uploaded = ApplicationDocument::where('land_transfer_application_id', $application->id)
            ->get()
            ->keyBy('required_document_id');

        $allRequirements = $transferorRequirements->concat($transfereeRequirements);
        $blockingRequirements = $allRequirements->filter(
            fn ($requirement) => method_exists($requirement, 'blocksAcceptance')
                ? $requirement->blocksAcceptance()
                : (bool) $requirement->is_mandatory
        );

        $pdf = Pdf::loadView('staff.applications.pdfs.acknowledgement-receipt', [
            'application' => $application,
            'transferorRequirements' => $transferorRequirements,
            'transfereeRequirements' => $transfereeRequirements,
            'uploaded' => $uploaded,
            'blockingRequirements' => $blockingRequirements,
        ])->setPaper('a4');

        $safeApplicationCode = str_replace(['/', '\\', ' '], '-', (string) $application->application_code);

        return $pdf->stream('LTC-Form-No-3-' . $safeApplicationCode . '.pdf');
    }
PHP;

$lastBrace = strrpos($content, "\n}");
if ($lastBrace === false) {
    fwrite(STDERR, "Could not find final class closing brace.\n");
    exit(1);
}

$content = substr_replace($content, $method . "\n", $lastBrace, 0);

file_put_contents($path, $content);

echo "Added LTC Form No. 3 acknowledgementPdf method to ApplicationClearanceController.\n";
