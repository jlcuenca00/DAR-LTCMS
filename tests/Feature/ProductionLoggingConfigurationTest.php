<?php

namespace Tests\Feature;

use App\Services\ProductionLoggingConfiguration;
use Tests\TestCase;

class ProductionLoggingConfigurationTest extends TestCase
{
    public function test_nested_stack_checks_debug_and_discarded_logs(): void
    {
        config([
            'logging.default' => 'outer',
            'logging.channels.outer' => ['driver' => 'stack', 'channels' => ['inner']],
            'logging.channels.inner' => ['driver' => 'stack', 'channels' => ['daily', 'null']],
            'logging.channels.daily.level' => 'debug',
        ]);
        $issues = app(ProductionLoggingConfiguration::class)->issues();
        $this->assertArrayHasKey('debug_log_level_daily', $issues);
        $this->assertArrayHasKey('logging_disabled', $issues);
    }

    public function test_empty_missing_and_cyclic_stacks_warn_without_recursion_failure(): void
    {
        foreach ([[], ['missing'], ['loop']] as $children) {
            config([
                'logging.default' => 'loop',
                'logging.channels.loop' => ['driver' => 'stack', 'channels' => $children],
            ]);
            $this->assertArrayHasKey('logging_configuration_invalid', app(ProductionLoggingConfiguration::class)->issues());
        }
    }

    public function test_daily_retention_is_bounded(): void
    {
        config(['logging.default' => 'daily', 'logging.channels.daily.level' => 'warning']);
        foreach ([0, -1, 366, 'invalid'] as $days) {
            config(['logging.channels.daily.days' => $days]);
            $this->assertArrayHasKey('logging_retention_invalid', app(ProductionLoggingConfiguration::class)->issues());
        }
        config(['logging.channels.daily.days' => 14]);
        $this->assertSame([], app(ProductionLoggingConfiguration::class)->issues());
    }

    public function test_single_logs_require_acknowledged_external_rotation(): void
    {
        config(['logging.default' => 'single', 'logging.channels.single.level' => 'warning', 'logging.external_rotation' => false]);
        $this->assertArrayHasKey('logging_rotation_unverified', app(ProductionLoggingConfiguration::class)->issues());
        config(['logging.external_rotation' => true]);
        $this->assertSame([], app(ProductionLoggingConfiguration::class)->issues());
    }
}
