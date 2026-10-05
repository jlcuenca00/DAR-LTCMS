<?php

// This output is consumed only by the private staging file in the backup script.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $environment = app(App\Services\ProductionBackupDatabaseConfiguration::class)->environment();
    foreach ($environment as $key => $value) {
        echo $key.'='.$value."\0";
    }
} catch (Throwable) {
    fwrite(STDERR, "Unable to resolve the effective PostgreSQL backup connection.\n");
    exit(1);
}
