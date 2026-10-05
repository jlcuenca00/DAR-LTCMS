<?php

namespace Tests\Feature;

use App\Services\DeploymentHttpSmokeChecker;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class DeploymentHttpSmokeTest extends TestCase
{
    private function responses(): array
    {
        return [
            'https://darltcms.me/up' => Http::response('healthy', 200),
            'https://darltcms.me/login' => Http::response('<form method="POST" action="https://darltcms.me/login"></form><link rel="stylesheet" href="/build/assets/app-abc.css"><script type="module" src="https://darltcms.me/build/assets/app-def.js"></script>', 200),
            'https://darltcms.me/build/assets/app-abc.css' => Http::response('body{}', 200, ['Content-Type' => 'text/css']),
            'https://darltcms.me/build/assets/app-def.js' => Http::response('console.log(1)', 200, ['Content-Type' => 'text/javascript']),
        ];
    }

    public function test_live_health_login_and_built_assets_must_all_pass(): void
    {
        Http::preventStrayRequests();
        Http::fake($this->responses());
        $result = app(DeploymentHttpSmokeChecker::class)->check('https://darltcms.me');
        $this->assertSame(['health' => true, 'login' => true, 'assets_checked' => 2], $result);
        Http::assertSentCount(4);
    }

    public function test_unhealthy_application_stops_before_login_and_assets(): void
    {
        Http::fake(array_replace($this->responses(), ['https://darltcms.me/up' => Http::response('Unavailable', 503)]));
        try {
            app(DeploymentHttpSmokeChecker::class)->check('https://darltcms.me');
            $this->fail('An unhealthy application must fail verification.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('/up', $error->getMessage());
            Http::assertSentCount(1);
        }
    }

    public function test_redirecting_login_does_not_count_as_success(): void
    {
        Http::fake(array_replace($this->responses(), ['https://darltcms.me/login' => Http::response('', 302, ['Location' => '/'])]));
        $this->expectException(RuntimeException::class);
        app(DeploymentHttpSmokeChecker::class)->check('https://darltcms.me');
    }

    public function test_missing_built_asset_fails_verification(): void
    {
        Http::fake(array_replace($this->responses(), ['https://darltcms.me/build/assets/app-def.js' => Http::response('Not found', 404)]));
        $this->expectException(RuntimeException::class);
        app(DeploymentHttpSmokeChecker::class)->check('https://darltcms.me');
    }

    public function test_asset_returning_html_instead_of_javascript_fails(): void
    {
        Http::fake(array_replace($this->responses(), ['https://darltcms.me/build/assets/app-def.js' => Http::response('<html>Login</html>', 200, ['Content-Type' => 'text/html'])]));
        $this->expectException(RuntimeException::class);
        app(DeploymentHttpSmokeChecker::class)->check('https://darltcms.me');
    }

    public function test_login_without_production_assets_fails(): void
    {
        Http::fake(array_replace($this->responses(), ['https://darltcms.me/login' => Http::response('<form method="post" action="/login"></form>', 200)]));
        $this->expectException(RuntimeException::class);
        app(DeploymentHttpSmokeChecker::class)->check('https://darltcms.me');
    }

    public function test_external_built_asset_is_rejected_without_fetching_it(): void
    {
        Http::fake(array_replace($this->responses(), ['https://darltcms.me/login' => Http::response('<form method="post" action="/login"></form><script type="module" src="https://other.test/build/assets/app.js"></script>', 200)]));
        try {
            app(DeploymentHttpSmokeChecker::class)->check('https://darltcms.me');
            $this->fail('An external built asset must be rejected.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('origin', $error->getMessage());
            Http::assertSentCount(2);
        }
    }
}
