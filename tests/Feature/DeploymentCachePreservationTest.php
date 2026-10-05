<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class DeploymentCachePreservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deployment_cache_refresh_preserves_active_authentication_limits(): void
    {
        config(['cache.default' => 'database']);
        $key = 'login-ip|192.0.2.100';
        RateLimiter::hit($key, 60);

        foreach (['config:clear', 'route:clear', 'view:clear'] as $command) {
            $this->assertSame(0, Artisan::call($command));
        }

        $this->assertSame(1, RateLimiter::attempts($key));
        RateLimiter::clear($key);

        $workflow = file_get_contents(base_path('.github/workflows/deploy.yml'));
        $this->assertStringNotContainsString('php artisan cache:clear', $workflow);
        $this->assertStringNotContainsString('php artisan optimize:clear', $workflow);
    }
}
