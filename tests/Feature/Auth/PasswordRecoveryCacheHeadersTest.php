<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class PasswordRecoveryCacheHeadersTest extends TestCase
{
    public function test_password_recovery_form_is_not_cacheable(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', '0');

        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
    }
}
