<?php

use App\Services\DataIntegrityScanner;
use App\Services\ProductionReadinessScanner;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('dar:scan-data-integrity {--json : Output the complete result as JSON}', function (DataIntegrityScanner $scanner) {
    $result = $scanner->scan();

    if ($this->option('json')) {
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    } else {
        $this->info('DAR-LTCMS Data Integrity Scan (read-only)');
        $this->line('Generated: '.$result['generated_at']);
        $this->line('Issue groups: '.$result['issue_groups']);
        $this->line('Affected records/groups: '.$result['issue_count']);

        if ($result['clean']) {
            $this->info('No integrity issues detected by the scanner.');
        } else {
            $this->newLine();
            foreach ($result['issues'] as $issue) {
                $this->warn($issue['code'].' — '.$issue['count']);
                $this->line($issue['message']);
            }
        }
    }

    return $result['clean'] ? 0 : 1;
})->purpose('Report DAR-LTCMS data-integrity problems without modifying records');

Artisan::command('dar:check-production-readiness {--json : Output the complete result as JSON}', function (ProductionReadinessScanner $scanner) {
    $result = $scanner->scan();

    if ($this->option('json')) {
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    } else {
        $this->info('DAR-LTCMS Production Readiness Check (read-only)');
        $this->line('Generated: '.$result['generated_at']);
        $this->line('Issues: '.$result['issue_count']);

        if ($result['clean']) {
            $this->info('Production security/configuration checks passed.');
        } else {
            $this->newLine();
            foreach ($result['issues'] as $issue) {
                $this->warn($issue['code']);
                $this->line($issue['message']);
            }
        }
    }

    return $result['clean'] ? 0 : 1;
})->purpose('Fail when DAR-LTCMS production security or deployment prerequisites are unsafe');