<?php

$path = __DIR__ . '/../routes/web.php';

if (! file_exists($path)) {
    fwrite(STDERR, "Cannot find routes/web.php. Add this route manually inside the staff route group:\n");
    fwrite(STDERR, "Route::get('/applications/{application}/form-4/pdf', [ApplicationClearanceController::class, 'form4Pdf'])->name('applications.form4.pdf');\n");
    exit(1);
}

$content = file_get_contents($path);

if (str_contains($content, "applications.form4.pdf")) {
    echo "LTC Form No. 4 PDF route already exists. No changes made.\n";
    exit(0);
}

$route = "        Route::get('/applications/{application}/form-4/pdf', [ApplicationClearanceController::class, 'form4Pdf'])\n"
    . "            ->name('applications.form4.pdf');\n";

$anchors = [
    "        Route::patch('/applications/{application}/form-4-review', [LandTransferApplicationController::class, 'updateForm4Review'])\n            ->name('applications.form4.update');\n",
    "        Route::get('/applications/{application}', [LandTransferApplicationController::class, 'show'])\n            ->name('applications.show');\n",
];

foreach ($anchors as $anchor) {
    $pos = strpos($content, $anchor);

    if ($pos !== false) {
        $content = substr($content, 0, $pos)
            . $anchor
            . $route
            . substr($content, $pos + strlen($anchor));

        file_put_contents($path, $content);
        echo "Inserted LTC Form No. 4 PDF route.\n";
        exit(0);
    }
}

fwrite(STDERR, "Could not safely auto-insert route. Add manually inside the staff route group:\n");
fwrite(STDERR, trim($route) . "\n");
exit(1);
