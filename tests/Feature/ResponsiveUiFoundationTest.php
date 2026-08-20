<?php

namespace Tests\Feature;

use Tests\TestCase;

class ResponsiveUiFoundationTest extends TestCase
{
    public function test_canonical_responsive_hardening_entry_is_loaded(): void
    {
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));
        $script = file_get_contents(resource_path('js/responsive-hardening.js'));

        $this->assertIsString($bootstrap);
        $this->assertIsString($script);
        $this->assertStringContainsString("import './responsive-hardening';", $bootstrap);
        $this->assertStringContainsString("import('../css/responsive-hardening.css')", $script);

        $this->assertStringNotContainsString("import './site-responsive';", $bootstrap);
        $this->assertStringNotContainsString("import './role-mobile-portal-nav';", $bootstrap);
        $this->assertStringNotContainsString("import './staff-mobile-portal-nav';", $bootstrap);
    }

    public function test_reflow_touch_pointer_and_viewport_contract_is_preserved(): void
    {
        $css = file_get_contents(resource_path('css/responsive-hardening.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('--dar-phone-gutter: 16px;', $css);
        $this->assertStringContainsString('--dar-touch-target: 44px;', $css);
        $this->assertStringContainsString('--dar-touch-target-coarse: 48px;', $css);
        $this->assertStringContainsString('@media (pointer: coarse)', $css);
        $this->assertStringContainsString('min-height: 100dvh;', $css);
        $this->assertStringContainsString('env(safe-area-inset-top, 0px)', $css);
        $this->assertStringContainsString('@media screen and (max-width: 1100px)', $css);
        $this->assertStringContainsString('@media screen and (max-width: 390px)', $css);
        $this->assertStringContainsString('@supports (container-type: inline-size)', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
    }

    public function test_dense_tables_scroll_locally_and_maps_remain_two_dimensional_exceptions(): void
    {
        $css = file_get_contents(resource_path('css/responsive-hardening.css'));
        $script = file_get_contents(resource_path('js/responsive-hardening.js'));

        $this->assertStringContainsString(':has(.no-responsive-table)', $css);
        $this->assertStringContainsString('overflow-x: auto !important;', $css);
        $this->assertStringContainsString('responsive-local-scroll', $script);
        $this->assertStringContainsString('Swipe horizontally to view all columns.', $script);
        $this->assertStringContainsString('.leaflet-control a', $css);
        $this->assertStringContainsString('min(58dvh, 540px)', $css);
    }

    public function test_auth_and_overlays_use_dynamic_viewport_and_safe_area_constraints(): void
    {
        $css = file_get_contents(resource_path('css/responsive-hardening.css'));

        $this->assertStringContainsString('body:has(.login-page)', $css);
        $this->assertStringContainsString('overflow-y: auto !important;', $css);
        $this->assertStringContainsString('.decision-modal-card', $css);
        $this->assertStringContainsString('.workflow-modal-card', $css);
        $this->assertStringContainsString('.profile-crop-dialog', $css);
        $this->assertStringContainsString('var(--dar-safe-bottom)', $css);
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
