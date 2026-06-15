<?php

$path = __DIR__ . '/../app/Http/Controllers/Staff/LandTransferApplicationController.php';

if (! file_exists($path)) {
    fwrite(STDERR, "Cannot find app/Http/Controllers/Staff/LandTransferApplicationController.php\n");
    exit(1);
}

$content = file_get_contents($path);

if (str_contains($content, "'documents.requiredDocument'")) {
    echo "documents.requiredDocument relation already loaded. No changes made.\n";
    exit(0);
}

$needle = "            'documents',\n";

if (! str_contains($content, $needle)) {
    fwrite(STDERR, "Could not find documents relation in show() load array. Add 'documents.requiredDocument' manually.\n");
    exit(1);
}

$content = str_replace($needle, "            'documents.requiredDocument',\n", $content);

file_put_contents($path, $content);

echo "Updated application show load to include documents.requiredDocument.\n";
