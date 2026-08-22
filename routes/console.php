<?php

use App\Models\User;
use App\Services\DataIntegrityScanner;
use App\Services\ProductionReadinessScanner;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

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

Artisan::command(
    'dar:provision-demo-access
        {--staff-password= : Staff demo password; omit to enter it securely}
        {--geodetic-password= : Geodetic demo password; omit to enter it securely}
        {--allow-production : Explicitly allow creation/update of the two presentation accounts in production}',
    function () {
        if (app()->environment('production') && ! $this->option('allow-production')) {
            $this->error('Production protection: rerun with --allow-production only when you intentionally want the two presentation accounts on the live system.');

            return 1;
        }

        $staffPassword = (string) ($this->option('staff-password') ?: $this->secret('Staff demo password'));
        $geodeticPassword = (string) ($this->option('geodetic-password') ?: $this->secret('Geodetic demo password'));

        $passwordIsStrong = static fn (string $password): bool =>
            strlen($password) >= 12
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/\d/', $password) === 1
            && preg_match('/[^A-Za-z0-9]/', $password) === 1;

        if (! $passwordIsStrong($staffPassword) || ! $passwordIsStrong($geodeticPassword)) {
            $this->error('Each demo password must be at least 12 characters and include lowercase, uppercase, a number, and a symbol.');

            return 1;
        }

        $accounts = [
            [
                'name' => 'DAR-LTCMS Staff Demo',
                'username' => 'staff.demo',
                'email' => 'staff.demo@dar-ltcms.local',
                'role' => User::ROLE_STAFF,
                'password' => $staffPassword,
            ],
            [
                'name' => 'DAR-LTCMS Geodetic Demo',
                'username' => 'geodetic.demo',
                'email' => 'geodetic.demo@dar-ltcms.local',
                'role' => User::ROLE_GEODETIC,
                'password' => $geodeticPassword,
            ],
        ];

        foreach ($accounts as $account) {
            $emailCollision = User::query()->where('email', $account['email'])->first();
            if ($emailCollision && $emailCollision->role !== $account['role']) {
                $this->error('Refusing to change '.$account['email'].' because it already belongs to a different role.');

                return 1;
            }

            $usernameCollision = User::query()
                ->where('username', $account['username'])
                ->where('email', '!=', $account['email'])
                ->first();

            if ($usernameCollision) {
                $this->error('Refusing to use username '.$account['username'].' because it already belongs to another account.');

                return 1;
            }
        }

        DB::transaction(function () use ($accounts): void {
            foreach ($accounts as $account) {
                User::query()->updateOrCreate(
                    ['email' => $account['email']],
                    [
                        'name' => $account['name'],
                        'username' => $account['username'],
                        'password' => $account['password'],
                        'role' => $account['role'],
                        'is_active' => true,
                        'must_change_password' => false,
                        'password_changed_at' => now(),
                    ]
                );
            }
        });

        $this->info('Presentation access is ready. No landowner, parcel, landholding, application, decision, ownership, or registry record was created or changed.');
        $this->line('Staff demo username: staff.demo');
        $this->line('Staff demo email: staff.demo@dar-ltcms.local');
        $this->line('Geodetic demo username: geodetic.demo');
        $this->line('Geodetic demo email: geodetic.demo@dar-ltcms.local');
        $this->warn('Passwords are intentionally not printed. Staff retains Staff permissions; Geodetic retains its limited technical access and cannot approve clearance applications or mutate ownership records.');

        return 0;
    }
)->purpose('Safely create or refresh the two presentation login accounts without seeding land transaction data');

Artisan::command('dar:disable-demo-access {--allow-production : Explicitly allow disabling the two presentation accounts in production}', function () {
    if (app()->environment('production') && ! $this->option('allow-production')) {
        $this->error('Production protection: rerun with --allow-production only when you intentionally want to disable the presentation accounts.');

        return 1;
    }

    $emails = [
        'staff.demo@dar-ltcms.local',
        'geodetic.demo@dar-ltcms.local',
    ];

    $count = User::query()
        ->whereIn('email', $emails)
        ->update(['is_active' => false]);

    $this->info("Disabled {$count} DAR-LTCMS presentation account(s). No records were deleted.");

    return 0;
})->purpose('Disable the presentation accounts without deleting system records');
