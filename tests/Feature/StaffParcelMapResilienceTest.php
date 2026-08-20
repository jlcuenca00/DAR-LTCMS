<?php

namespace Tests\Feature;

use Tests\TestCase;

class StaffParcelMapResilienceTest extends TestCase
{
    public function test_staff_parcel_map_renders_search_before_bundled_leaflet_initialization(): void
    {
        $view = file_get_contents(resource_path('views/staff/maps/parcel-map.blade.php'));
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));

        $this->assertStringContainsString('renderSearchResults();', $view);
        $this->assertStringContainsString('initializeMap();', $view);
        $this->assertLessThan(
            strpos($view, 'initializeMap();'),
            strpos($view, 'renderSearchResults();')
        );

        $this->assertStringContainsString("import L from 'leaflet';", $bootstrap);
        $this->assertStringContainsString("import 'leaflet/dist/leaflet.css';", $bootstrap);
        $this->assertStringContainsString('typeof window.L', $view);
        $this->assertStringNotContainsString('cdn.jsdelivr.net/npm/leaflet@1.9.4', $view);
        $this->assertStringNotContainsString('unpkg.com/leaflet@1.9.4', $view);
        $this->assertStringContainsString('The parcel list remains available.', $view);
    }

    public function test_staff_parcel_map_uses_dynamic_viewport_sizing(): void
    {
        $view = file_get_contents(resource_path('views/staff/maps/parcel-map.blade.php'));

        $this->assertStringContainsString('calc(100dvh - 212px)', $view);
        $this->assertStringContainsString('min(58dvh, 560px)', $view);
        $this->assertStringNotContainsString('calc(100vh - 212px)', $view);
    }
}
