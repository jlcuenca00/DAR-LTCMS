<?php

namespace App\Services;

use Monolog\Handler\NullHandler;

class ProductionLoggingConfiguration
{
    public function issues(): array
    {
        $issues = [];
        $this->inspect((string) config('logging.default'), [], $issues);

        return $issues;
    }

    private function inspect(string $name, array $ancestors, array &$issues): void
    {
        $channel = config("logging.channels.{$name}");
        if (in_array($name, $ancestors, true) || ! is_array($channel) || empty($channel['driver'])) {
            $issues['logging_configuration_invalid'] = 'The active logging graph contains a missing, invalid, or cyclic channel.';
            return;
        }

        $driver = $channel['driver'];
        if ($driver === 'stack') {
            $children = $channel['channels'] ?? [];
            if (! is_array($children) || $children === []) {
                $issues['logging_configuration_invalid'] = 'The active logging stack must contain a configured channel.';
                return;
            }
            foreach ($children as $child) {
                $this->inspect(trim((string) $child), [...$ancestors, $name], $issues);
            }
            return;
        }

        if ($driver === 'null' || ($channel['handler'] ?? null) === NullHandler::class) {
            $issues['logging_disabled'] = 'An active log channel discards application logs.';
            return;
        }

        $level = strtolower((string) ($channel['level'] ?? 'debug'));
        if ($level === 'debug') {
            $issues['debug_log_level_'.$name] = "Production log channel {$name} should not run at debug level.";
        } elseif (! in_array($level, ['info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'], true)) {
            $issues['logging_level_invalid'] = 'An active log channel has an invalid severity level.';
        }

        if ($driver === 'daily') {
            $days = filter_var($channel['days'] ?? 7, FILTER_VALIDATE_INT);
            if ($days === false || $days < 1 || $days > 365) {
                $issues['logging_retention_invalid'] = 'Daily log retention must be between 1 and 365 days.';
            }
        } elseif ($driver === 'single' && config('logging.external_rotation') !== true) {
            $issues['logging_rotation_unverified'] = 'Single-file logging requires verified external rotation; prefer LOG_STACK=daily with bounded retention.';
        }
    }
}
