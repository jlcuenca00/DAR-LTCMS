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

Artisan::command('dar:check-production-readiness {--json : Output the complete result as JSON} {--strict : Treat release warnings as failures}', function (ProductionReadinessScanner $scanner) {
    $result = $scanner->scan();
    $strict = (bool) $this->option('strict');

    if ($this->option('json')) {
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    } else {
        $this->info('DAR-LTCMS Production Readiness Check (read-only)');
        $this->line('Generated: '.$result['generated_at']);
        $this->line('Blocking issues: '.$result['blocking_count']);
        $this->line('Warnings: '.$result['warning_count']);

        if ($result['clean']) {
            $this->info('Production security/configuration checks passed with no warnings.');
        } elseif ($result['ready']) {
            $this->warn('Core deployment checks passed, but release warnings remain.');
        } else {
            $this->error('Production deployment blockers were detected.');
        }

        if (! $result['clean']) {
            $this->newLine();
            foreach ($result['issues'] as $issue) {
                $label = strtoupper($issue['severity']).' — '.$issue['code'];
                $issue['severity'] === 'blocking' ? $this->error($label) : $this->warn($label);
                $this->line($issue['message']);
            }
        }
    }

    if (! $result['ready']) {
        return 1;
    }

    return $strict && ! $result['clean'] ? 1 : 0;
})->purpose('Fail when DAR-LTCMS production security or deployment prerequisites are unsafe');

Artisan::command('dar:release-check {--json : Output the complete final-release result as JSON}', function (
    DataIntegrityScanner $dataIntegrityScanner,
    ProductionReadinessScanner $productionReadinessScanner
) {
    $dataIntegrity = $dataIntegrityScanner->scan();
    $productionReadiness = $productionReadinessScanner->scan();
    $releaseReady = $dataIntegrity['clean'] && $productionReadiness['clean'];

    $result = [
        'release_ready' => $releaseReady,
        'generated_at' => now()->toIso8601String(),
        'read_only' => true,
        'data_integrity' => $dataIntegrity,
        'production_readiness' => $productionReadiness,
    ];

    if ($this->option('json')) {
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $releaseReady ? 0 : 1;
    }

    $this->info('DAR-LTCMS Final Release Check (read-only)');
    $this->line('Data integrity issues: '.$dataIntegrity['issue_count']);
    $this->line('Production blockers: '.$productionReadiness['blocking_count']);
    $this->line('Production warnings: '.$productionReadiness['warning_count']);

    if ($releaseReady) {
        $this->newLine();
        $this->info('FINAL RELEASE READY: data and production configuration checks are clean.');

        return 0;
    }

    $this->newLine();
    $this->error('FINAL RELEASE NOT READY. Resolve the items below before creating v1.0.0.');

    foreach ($dataIntegrity['issues'] as $issue) {
        $this->warn('DATA — '.$issue['code'].' — '.$issue['count']);
        $this->line($issue['message']);
    }

    foreach ($productionReadiness['issues'] as $issue) {
        $label = 'PRODUCTION '.strtoupper($issue['severity']).' — '.$issue['code'];
        $issue['severity'] === 'blocking' ? $this->error($label) : $this->warn($label);
        $this->line($issue['message']);
    }

    return 1;
})->purpose('Run the strict read-only data and production checks required before a DAR-LTCMS final release');
