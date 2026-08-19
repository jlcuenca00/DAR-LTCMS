<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccountMenuPanelTest extends TestCase
{
    public function test_shared_account_panel_exposes_role_appropriate_account_actions(): void
    {
        $view = file_get_contents(resource_path('views/components/account-menu.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('account-panel-identity', $view);
        $this->assertStringContainsString('Manage Profile', $view);
        $this->assertStringContainsString('Account &amp; Security', $view);
        $this->assertStringContainsString('Notifications', $view);
        $this->assertStringContainsString('User Management', $view);
        $this->assertStringContainsString('Display &amp; Accessibility', $view);
        $this->assertStringContainsString('Help &amp; Support', $view);
        $this->assertStringContainsString('Log Out', $view);
        $this->assertStringContainsString('$administrationRoute', $view);
    }

    public function test_account_panel_preserves_mobile_and_accessibility_behavior(): void
    {
        $view = file_get_contents(resource_path('views/components/account-menu.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('bottom: 12px !important;', $view);
        $this->assertStringContainsString('left: 16px !important;', $view);
        $this->assertStringContainsString('right: 16px !important;', $view);
        $this->assertStringContainsString('data-account-reduce-motion', $view);
        $this->assertStringContainsString('dar-ltcms-reduce-motion', $view);
        $this->assertStringContainsString('dar-reduce-motion', $view);
    }

    public function test_account_panel_does_not_offer_profile_switching(): void
    {
        $view = file_get_contents(resource_path('views/components/account-menu.blade.php'));

        $this->assertIsString($view);
        $this->assertStringNotContainsString('See all profiles', $view);
        $this->assertStringNotContainsString('Switch profile', $view);
        $this->assertStringNotContainsString('Business Suite', $view);
    }
}
