<?php

namespace App\Services;

use Illuminate\Support\ConfigurationUrlParser;
use RuntimeException;
use Throwable;

class ProductionMailConfiguration
{
    /** Validate configuration only; never contact a mail server or expose credentials. */
    public function issues(?string $mailer = null): array
    {
        $issues = [];
        $this->inspect($mailer ?? (string) config('mail.default'), [], $issues);

        return array_unique($issues);
    }

    public function assertDeliverable(?string $mailer = null): void
    {
        if ($this->issues($mailer) !== []) {
            throw new RuntimeException('Production email configuration is not suitable for delivery.');
        }
    }

    private function inspect(string $name, array $visited, array &$issues): void
    {
        if (in_array($name, $visited, true)) {
            $issues['mail_configuration_invalid'] = 'The active mailer configuration contains a cycle.';
            return;
        }
        $visited[] = $name;
        $config = config('mail.mailers.'.$name);
        if (! is_array($config)) {
            $issues['mail_configuration_invalid'] = 'The active mailer must reference a configured transport.';
            return;
        }
        try {
            if (isset($config['url'])) {
                $config = (new ConfigurationUrlParser)->parseConfiguration($config);
                $config['transport'] = $config['driver'] ?? null;
            }
        } catch (Throwable) {
            $issues['mail_configuration_invalid'] = 'The active mail transport URL is malformed.';
            return;
        }
        $transport = $config['transport'] ?? null;
        if (in_array($transport, ['log', 'array'], true)) {
            $issues['mail_not_deliverable'] = 'Production mail must not use a log/array transport, including inside failover or roundrobin mailers.';
            return;
        }
        if (in_array($transport, ['failover', 'roundrobin'], true)) {
            $children = $config['mailers'] ?? [];
            if (! is_array($children) || $children === []) {
                $issues['mail_configuration_invalid'] = 'The active composite mailer must contain delivery transports.';
                return;
            }
            foreach ($children as $child) {
                $this->inspect((string) $child, $visited, $issues);
            }
            return;
        }
        if (! in_array($transport, ['smtp', 'sendmail', 'ses', 'ses-v2', 'postmark', 'resend', 'mailgun'], true)) {
            $issues['mail_configuration_invalid'] = 'The active mailer transport is unsupported.';
            return;
        }
        if ($transport === 'smtp') {
            if (blank($config['host'] ?? null) || ! is_numeric($config['port'] ?? null)
                || (int) $config['port'] < 1 || (int) $config['port'] > 65535
                || ! in_array($config['scheme'] ?? null, [null, '', 'smtp', 'smtps'], true)) {
                $issues['mail_configuration_invalid'] = 'SMTP requires a host, valid port, and smtp/smtps scheme (or automatic scheme selection).';
            }
            if (! is_numeric($config['timeout'] ?? null) || (float) $config['timeout'] < 1 || (float) $config['timeout'] > 30) {
                $issues['mail_timeout_unsafe'] = 'SMTP must use an explicit timeout between 1 and 30 seconds.';
            }
        }
    }
}
