<?php

namespace Tests\Feature;

use App\Services\ProductionMailConfiguration;
use Tests\TestCase;

class ProductionMailConfigurationTest extends TestCase
{
    public function test_deliverable_smtp_and_its_failover_pass_configuration_checks(): void
    {
        config(['mail.default' => 'failover']);
        $this->assertSame([], app(ProductionMailConfiguration::class)->issues());
        $transport = app('mail.manager')->mailer('smtp')->getSymfonyTransport();
        $this->assertSame(15.0, $transport->getStream()->getTimeout());
    }

    public function test_nested_log_fallback_is_rejected_even_when_mailer_has_another_name(): void
    {
        config([
            'mail.default' => 'outer',
            'mail.mailers.outer' => ['transport' => 'roundrobin', 'mailers' => ['failover']],
            'mail.mailers.failover.mailers' => ['smtp', 'silent'],
            'mail.mailers.silent' => ['transport' => 'log'],
        ]);
        $this->assertArrayHasKey('mail_not_deliverable', app(ProductionMailConfiguration::class)->issues());
    }

    public function test_invalid_scheme_and_missing_timeout_are_reported(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.scheme' => 'tls', 'mail.mailers.smtp.timeout' => null]);
        $issues = app(ProductionMailConfiguration::class)->issues();
        $this->assertArrayHasKey('mail_configuration_invalid', $issues);
        $this->assertArrayHasKey('mail_timeout_unsafe', $issues);
    }

    public function test_transport_url_cannot_hide_a_non_delivery_transport(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.url' => 'log://localhost']);
        $this->assertArrayHasKey('mail_not_deliverable', app(ProductionMailConfiguration::class)->issues());
    }

    public function test_cycles_and_unknown_mailers_fail_without_recursion_or_network_calls(): void
    {
        config(['mail.default' => 'failover', 'mail.mailers.failover.mailers' => ['failover', 'missing']]);
        $this->assertArrayHasKey('mail_configuration_invalid', app(ProductionMailConfiguration::class)->issues());
    }
}
