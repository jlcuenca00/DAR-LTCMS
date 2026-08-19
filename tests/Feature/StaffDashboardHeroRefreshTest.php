<?php

namespace Tests\Feature;

use Tests\TestCase;

class StaffDashboardHeroRefreshTest extends TestCase
{
    public function test_staff_dashboard_hero_refresh_assets_are_loaded(): void
    {
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));
        $css = file_get_contents(resource_path('css/staff-dashboard-hero.css'));
        $script = file_get_contents(resource_path('js/staff-dashboard-hero.js'));

        $this->assertIsString($bootstrap);
        $this->assertIsString($css);
        $this->assertIsString($script);

        $this->assertStringContainsString("import './staff-dashboard-hero';", $bootstrap);
        $this->assertStringContainsString('grid-template-columns: repeat(3, minmax(0, 1fr));', $css);
        $this->assertStringNotContainsString("content: 'Showing below';", $css);
        $this->assertStringContainsString('Process and monitor clearance applications.', $script);
        $this->assertStringContainsString('active application', $script);
        $this->assertStringContainsString('Awaiting legal review', $script);
        $this->assertStringContainsString('In endorsement', $script);
        $this->assertStringContainsString('Ready for release', $script);
        $this->assertStringNotContainsString('Select a queue to filter the preview below.', $script);
    }

    public function test_staff_dashboard_hero_keeps_mobile_edge_spacing(): void
    {
        $css = file_get_contents(resource_path('css/staff-dashboard-hero.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('@media (max-width: 560px)', $css);
        $this->assertStringContainsString('padding: 18px 16px;', $css);
        $this->assertStringContainsString('.staff-dashboard .hero-action', $css);
    }
}
