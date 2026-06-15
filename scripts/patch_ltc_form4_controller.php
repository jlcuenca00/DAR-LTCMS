<?php

$path = __DIR__ . '/../app/Http/Controllers/Staff/LandTransferApplicationController.php';

if (! file_exists($path)) {
    fwrite(STDERR, "Cannot find app/Http/Controllers/Staff/LandTransferApplicationController.php\n");
    exit(1);
}

$content = file_get_contents($path);

if (str_contains($content, "public function updateForm4Review(")) {
    echo "updateForm4Review already exists. No changes made.\n";
    exit(0);
}

$method = <<<'PHP'

    public function updateForm4Review(Request $request, LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return back()->with('error', 'LTC Form No. 4 review details are locked after release or denial.');
        }

        $validated = $request->validate([
            'ltc_form4_subject_land_findings' => ['nullable', 'array'],
            'ltc_form4_subject_land_findings.*' => ['nullable', 'string', 'max:120'],
            'ltc_form4_recommendation_findings' => ['nullable', 'array'],
            'ltc_form4_recommendation_findings.*' => ['nullable', 'string', 'max:120'],
            'ltc_form4_recommendation_decision' => ['nullable', 'in:approval,denial'],
            'ltc_form4_other_findings' => ['nullable', 'string', 'max:2000'],
            'ltc_form4_certified_at' => ['nullable', 'date'],
            'ltc_form4_certifying_officer_name' => ['nullable', 'string', 'max:255'],
        ]);

        $application->forceFill([
            'ltc_form4_subject_land_findings' => array_values($validated['ltc_form4_subject_land_findings'] ?? []),
            'ltc_form4_recommendation_findings' => array_values($validated['ltc_form4_recommendation_findings'] ?? []),
            'ltc_form4_recommendation_decision' => $validated['ltc_form4_recommendation_decision'] ?? null,
            'ltc_form4_other_findings' => $validated['ltc_form4_other_findings'] ?? null,
            'ltc_form4_certified_at' => $validated['ltc_form4_certified_at'] ?? null,
            'ltc_form4_certifying_officer_name' => $validated['ltc_form4_certifying_officer_name'] ?? null,
        ])->save();

        AuditLogger::record(
            'ltc_form4_review_updated',
            $application,
            $application,
            [
                'recommendation_decision' => $application->ltc_form4_recommendation_decision,
                'subject_land_findings_count' => count((array) $application->ltc_form4_subject_land_findings),
                'recommendation_findings_count' => count((array) $application->ltc_form4_recommendation_findings),
            ],
            Auth::id()
        );

        return back()->with('success', 'LTC Form No. 4 attestation and recommendation details updated.');
    }
PHP;

$lastBrace = strrpos($content, "\n}");
if ($lastBrace === false) {
    fwrite(STDERR, "Could not find final class closing brace.\n");
    exit(1);
}

$content = substr_replace($content, $method . "\n", $lastBrace, 0);

file_put_contents($path, $content);

echo "Added updateForm4Review method to LandTransferApplicationController.\n";
