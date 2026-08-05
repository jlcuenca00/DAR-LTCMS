<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_html_pages_receive_security_and_no_cache_headers(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($user)->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }
}
