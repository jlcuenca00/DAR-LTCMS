<?php

namespace Tests\Feature;

use Tests\TestCase;

class ResponsiveUiFoundationTest extends TestCase
{
    public function test_sitewide_responsive_assets_are_loaded(): void
    {
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));

        $this->assertIsString($bootstrap);
        $this->assertStringContainsString("import './site-responsive';", $bootstrap);
        $this->assertStringContainsString("import '../css/site-responsive-system.css';", $bootstrap);
        $this->assertStringContainsString("import '../css/site-responsive-refinements.css';", $bootstrap);
    }

    public function test_mobile_gutter_reflow_and_touch_rules_are_preserved(): void
    {
        $responsive = file_get_contents(resource_path('css/site-responsive-system.css'));
        $refinements = file_get_contents(resource_path('css/site-responsive-refinements.css'));

        $this->assertIsString($responsive);
        $this->assertIsString($refinements);

        $this->assertStringContainsString('--dar-gutter-phone: 16px;', $responsive);
        $this->assertStringContainsString('--dar-touch-target: 44px;', $responsive);
        $this->assertStringContainsString('@media screen and (max-width: 390px)', $responsive);
        $this->assertStringContainsString('.lo-sidebar.is-mobile-open', $responsive);
        $this->assertStringContainsString('.lo-mobile-nav-toggle', $refinements);
        $this->assertStringContainsString(':has(.no-responsive-table)', $refinements);
    }

    public function test_landowner_mobile_navigation_is_accessible_and_collapsible(): void
    {
        $script = file_get_contents(resource_path('js/site-responsive.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString("toggle.setAttribute('aria-controls'", $script);
        $this->assertStringContainsString("toggle.setAttribute('aria-expanded'", $script);
        $this->assertStringContainsString("sidebar.classList.toggle('is-mobile-open'", $script);
        $this->assertStringContainsString("event.key !== 'Escape'", $script);
    }

    public function test_notification_center_exposes_shared_responsive_hooks(): void
    {
        $view = file_get_contents(resource_path('views/notifications/partials/list.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('notification-center-summary', $view);
        $this->assertStringContainsString('notification-center-item', $view);
        $this->assertStringContainsString('notification-center-actions', $view);
    }
}
