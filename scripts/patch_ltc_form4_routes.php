<?php

$path = __DIR__ . '/../routes/web.php';

if (! file_exists($path)) {
    fwrite(STDERR, "Cannot find routes/web.php. Add this route manually inside the staff application route group:\n");
    fwrite(STDERR, "Route::patch('/staff/applications/{application}/form-4-review', [LandTransferApplicationController::class, 'updateForm4Review'])->name('staff.applications.form4.update');\n");
    exit(1);
}

$content = file_get_contents($path);

if (str_contains($content, "staff.applications.form4.update")) {
    echo "LTC Form No. 4 route already exists. No changes made.\n";
    exit(0);
}

$route = "Route::patch('/staff/applications/{application}/form-4-review', [LandTransferApplicationController::class, 'updateForm4Review'])->name('staff.applications.form4.update');";

$anchors = [
    "Route::get('/staff/applications/{application}', [LandTransferApplicationController::class, 'show'])->name('staff.applications.show');",
    "Route::resource('applications', LandTransferApplicationController::class)",
    "staff.applications.show",
];

foreach ($anchors as $anchor) {
    $pos = strpos($content, $anchor);
    if ($pos !== false) {
        $lineEnd = strpos($content, "\n", $pos);
        if ($lineEnd === false) {
            $lineEnd = strlen($content);
        }

        $content = substr($content, 0, $lineEnd + 1) . $route . "\n" . substr($content, $lineEnd + 1);
        file_put_contents($path, $content);
        echo "Inserted LTC Form No. 4 route.\n";
        exit(0);
    }
}

fwrite(STDERR, "Could not safely auto-insert route. Add manually:\n$route\n");
exit(1);
