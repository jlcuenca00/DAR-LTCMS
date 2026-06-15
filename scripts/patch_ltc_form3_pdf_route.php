<?php

$path = __DIR__ . '/../routes/web.php';

if (! file_exists($path)) {
    fwrite(STDERR, "Cannot find routes/web.php. Add this route manually inside the staff route group:\n");
    fwrite(STDERR, "Route::get('/applications/{application}/acknowledgement/pdf', [ApplicationClearanceController::class, 'acknowledgementPdf'])->name('applications.acknowledgement.pdf');\n");
    exit(1);
}

$content = file_get_contents($path);

if (str_contains($content, "applications.acknowledgement.pdf")) {
    echo "LTC Form No. 3 PDF route already exists. No changes made.\n";
    exit(0);
}

$route = "        Route::get('/applications/{application}/acknowledgement/pdf', [ApplicationClearanceController::class, 'acknowledgementPdf'])\n"
    . "            ->name('applications.acknowledgement.pdf');\n";

$anchors = [
    "        Route::get('/applications/{application}', [LandTransferApplicationController::class, 'show'])\n            ->name('applications.show');\n",
    "        Route::get('/applications/{application}/clearance', [ApplicationClearanceController::class, 'show'])\n            ->name('applications.clearance.show');\n",
];

foreach ($anchors as $anchor) {
    $pos = strpos($content, $anchor);

    if ($pos !== false) {
        $content = substr($content, 0, $pos)
            . $anchor
            . $route
            . substr($content, $pos + strlen($anchor));

        file_put_contents($path, $content);
        echo "Inserted LTC Form No. 3 PDF route.\n";
        exit(0);
    }
}

fwrite(STDERR, "Could not safely auto-insert route. Add manually inside the staff route group:\n");
fwrite(STDERR, trim($route) . "\n");
exit(1);
