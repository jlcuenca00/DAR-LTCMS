<?php

namespace Tests\Feature;

use Tests\TestCase;

class PortalTopbarControlConsistencyTest extends TestCase
{
    public function test_shared_account_component_normalizes_portal_topbar_controls(): void
    {
        $component = file_get_contents(resource_path('views/components/account-menu.blade.php'));

        $this->assertIsString($component);
        $this->assertStringContainsString('.notification-bell-link', $component);
        $this->assertStringContainsString('.onboarding-help-button', $component);
        $this->assertStringContainsString('.geo-access-chip, .lo-access-chip', $component);
        $this->assertStringContainsString('.account-menu-trigger', $component);
        $this->assertStringContainsString('width: 44px !important;', $component);
        $this->assertStringContainsString('height: 44px !important;', $component);
        $this->assertStringContainsString('min-height: 44px !important;', $component);
        $this->assertStringContainsString('border: 1px solid #bbd7c4 !important;', $component);
        $this->assertStringContainsString('transform: none !important;', $component);
        $this->assertStringContainsString('gap: 10px !important;', $component);
        $this->assertStringContainsString('font-weight: 700 !important;', $component);
    }
}
