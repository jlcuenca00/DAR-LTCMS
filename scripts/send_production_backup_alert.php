<?php

// No backup credentials or raw error logs are included in messages.
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require __DIR__.'/../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $arguments = array_slice($argv, 1);
    if ($arguments !== [] && $arguments !== ['--test']) {
        throw new RuntimeException('Use no arguments for failure alerts or --test for a test message.');
    }

    $recipientFile = rtrim((string) getenv('HOME'), '/').'/.config/dar-ltcms/backup-alert-email';
    $recipient = is_file($recipientFile) ? trim((string) file_get_contents($recipientFile)) : '';
    if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Configure a valid backup-alert-email file first.');
    }

    $transport = config('mail.mailers.'.config('mail.default').'.transport');
    if (! is_string($transport) || in_array($transport, ['log', 'array'], true)) {
        throw new RuntimeException('A delivery-capable mail transport is required.');
    }

    $test = $arguments === ['--test'];
    $subject = $test ? 'DAR-LTCMS backup alert test' : 'DAR-LTCMS backup FAILED';
    $body = $test
        ? "This is a test of DAR-LTCMS backup failure email alerts. No backup failed for this test."
        : "The DAR-LTCMS production backup failed. Check the server backup logs and resolve the cause. The website database was not restored or replaced by this notification.";
    $body .= "\n\nTime: ".now()->toIso8601String()."\nServer: ".gethostname();

    Illuminate\Support\Facades\Mail::raw($body, function ($message) use ($recipient, $subject): void {
        $message->to($recipient)->subject($subject);
    });
    echo "Backup alert accepted by the configured mail transport; verify inbox receipt.\n";
} catch (Throwable $error) {
    // Mail exceptions can contain credentials or transport details; keep output generic.
    fwrite(STDERR, "Backup alert could not be sent. Check recipient configuration and mail service privately.\n");
    exit(1);
}
