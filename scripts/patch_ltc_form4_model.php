<?php

$path = __DIR__ . '/../app/Models/LandTransferApplication.php';

if (! file_exists($path)) {
    fwrite(STDERR, "Cannot find app/Models/LandTransferApplication.php\n");
    exit(1);
}

$content = file_get_contents($path);

if (! str_contains($content, "'ltc_form4_subject_land_findings' => 'array'")) {
    $needle = "    protected \$casts = [\n";

    if (str_contains($content, $needle)) {
        $content = str_replace($needle, $needle .
            "        'ltc_form4_subject_land_findings' => 'array',\n" .
            "        'ltc_form4_recommendation_findings' => 'array',\n" .
            "        'ltc_form4_certified_at' => 'date',\n",
            $content
        );
    } else {
        $insertAfter = "class LandTransferApplication extends Model\n{\n";
        $castsBlock = "    protected \$casts = [\n" .
            "        'ltc_form4_subject_land_findings' => 'array',\n" .
            "        'ltc_form4_recommendation_findings' => 'array',\n" .
            "        'ltc_form4_certified_at' => 'date',\n" .
            "    ];\n\n";

        if (! str_contains($content, $insertAfter)) {
            fwrite(STDERR, "Could not find model class opening.\n");
            exit(1);
        }

        $content = str_replace($insertAfter, $insertAfter . $castsBlock, $content);
    }
}

file_put_contents($path, $content);

echo "Patched LandTransferApplication casts for LTC Form No. 4 fields.\n";
