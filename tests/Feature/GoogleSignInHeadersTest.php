<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class GoogleSignInHeadersTest extends TestCase
{
    public function test_google_resources_are_allowed_only_on_configured_sign_in_pages(): void
    {
        $this->app->instance('env', 'production');

        foreach (['login', 'register', 'home'] as $name) {
            foreach (['test-client', null] as $clientId) {
                config(['services.google.client_id' => $clientId]);
                $request = Request::create('https://example.test/'.$name);
                $route = (new Route('GET', $name, fn () => null))->name($name);
                $request->setRouteResolver(fn () => $route);
                $response = (new SecurityHeaders)->handle($request, fn () => response('OK'));
                $csp = $response->headers->get('Content-Security-Policy');
                $enabled = $clientId && in_array($name, ['login', 'register'], true);

                if ($enabled) {
                    $this->assertStringContainsString('https://accounts.google.com/gsi/client', $csp);
                    $this->assertStringContainsString('https://accounts.google.com/gsi/style', $csp);
                    $this->assertStringContainsString("connect-src 'self' https://accounts.google.com/gsi/", $csp);
                    $this->assertStringContainsString("frame-src 'self' https://accounts.google.com/gsi/", $csp);
                } else {
                    $this->assertStringNotContainsString('accounts.google.com', $csp);
                }

                $this->assertSame($enabled ? 'same-origin-allow-popups' : 'same-origin', $response->headers->get('Cross-Origin-Opener-Policy'));
                $this->assertStringContainsString("form-action 'self'", $csp);
                $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
            }
        }
    }
}
