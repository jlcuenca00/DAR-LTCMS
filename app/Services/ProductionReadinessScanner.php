<?php

namespace App\Services;

class ProductionReadinessScanner
{
    public function scan(): array
    {
        $issues = [];
        $appUrl = (string) config('app.url');
        $appHost = parse_url($appUrl, PHP_URL_HOST);
        $appScheme = parse_url($appUrl, PHP_URL_SCHEME);

        $this->require($issues, config('app.env') === 'production', 'environment_not_production', 'APP_ENV must be production.');
        $this->require($issues, config('app.debug') === false, 'debug_enabled', 'APP_DEBUG must be false in production.');
        $this->require($issues, $appScheme === 'https' && filled($appHost), 'app_url_not_https', 'APP_URL must be a complete HTTPS URL for the deployed DAR-LTCMS host.');
        $this->require($issues, filled(config('app.key')), 'app_key_missing', 'APP_KEY must be configured before production deployment.');

        $this->require($issues, config('session.driver') === 'database', 'session_driver_not_database', 'SESSION_DRIVER must remain database for the production deployment.');
        $this->require($issues, config('session.encrypt') === true, 'session_encryption_disabled', 'SESSION_ENCRYPT must be true.');
        $this->require($issues, config('session.secure') === true, 'session_cookie_not_secure', 'SESSION_SECURE_COOKIE must be true for HTTPS production.');
        $this->require($issues, config('session.http_only') === true, 'session_cookie_not_http_only', 'SESSION_HTTP_ONLY must remain true.');
        $this->require($issues, in_array(config('session.same_site'), ['lax', 'strict'], true), 'session_same_site_unsafe', 'SESSION_SAME_SITE must be lax or strict for this same-site application.');

        $this->require($issues, config('filesystems.default') === 'local', 'default_filesystem_not_private', 'FILESYSTEM_DISK must remain local so supporting documents use private storage.');
        $this->require(
            $issues,
            str_contains((string) config('filesystems.disks.public.url'), '/staff/protected-storage'),
            'source_storage_url_not_protected',
            'The legacy source-file disk URL must resolve through the authenticated Staff protected-storage route.'
        );
        $this->require(
            $issues,
            ! is_link(public_path('storage')) && ! file_exists(public_path('storage')),
            'public_storage_exposed',
            'public/storage must not exist in production because source-package scans are sensitive administrative records.'
        );
        $this->require($issues, is_dir(storage_path('app/private')) && is_writable(storage_path('app/private')), 'private_storage_not_writable', 'storage/app/private must exist and be writable.');
        $this->require($issues, is_dir(storage_path('app/public')) && is_writable(storage_path('app/public')), 'legacy_source_storage_not_writable', 'storage/app/public must remain writable for protected legacy source-package scans.');

        $mailDriver = (string) config('mail.default');
        $this->recommend($issues, ! in_array($mailDriver, ['log', 'array'], true), 'mail_not_deliverable', 'MAIL_MAILER should use a real delivery transport in production so password-recovery messages can be delivered.');

        foreach ($this->activeLogLevels() as $channel => $level) {
            $this->recommend(
                $issues,
                strtolower((string) $level) !== 'debug',
                'debug_log_level_'.$channel,
                "Production log channel {$channel} should not run at debug level."
            );
        }

        $blockingCount = collect($issues)->where('severity', 'blocking')->count();
        $warningCount = collect($issues)->where('severity', 'warning')->count();

        return [
            'clean' => $issues === [],
            'ready' => $blockingCount === 0,
            'issue_count' => count($issues),
            'blocking_count' => $blockingCount,
            'warning_count' => $warningCount,
            'generated_at' => now()->toIso8601String(),
            'read_only' => true,
            'issues' => $issues,
        ];
    }

    private function require(array &$issues, bool $condition, string $code, string $message): void
    {
        $this->addIssue($issues, $condition, 'blocking', $code, $message);
    }

    private function recommend(array &$issues, bool $condition, string $code, string $message): void
    {
        $this->addIssue($issues, $condition, 'warning', $code, $message);
    }

    private function addIssue(array &$issues, bool $condition, string $severity, string $code, string $message): void
    {
        if ($condition) {
            return;
        }

        $issues[] = [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
        ];
    }

    private function activeLogLevels(): array
    {
        $default = (string) config('logging.default');
        $channel = config("logging.channels.{$default}", []);

        if (($channel['driver'] ?? null) !== 'stack') {
            return [$default => $channel['level'] ?? null];
        }

        $levels = [];

        foreach ((array) ($channel['channels'] ?? []) as $stackChannel) {
            $levels[$stackChannel] = config("logging.channels.{$stackChannel}.level");
        }

        return $levels;
    }
}
