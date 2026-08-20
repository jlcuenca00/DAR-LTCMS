<?php

namespace Tests\Feature;

use Tests\TestCase;

class StaffParcelMapResilienceTest extends TestCase
{
    public function test_staff_parcel_map_renders_search_before_leaflet_initialization_and_has_cdn_fallback(): void
    {
        $view = file_get_contents(resource_path('views/staff/maps/parcel-map.blade.php'));

        $this->assertStringContainsString("renderSearchResults();", $view);
        $this->assertStringContainsString("loadLeafletScript();", $view);
        $this->assertLessThan(
            strpos($view, "loadLeafletScript();"),
            strpos($view, "renderSearchResults();")
        );

        $this->assertStringContainsString('cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js', $view);
        $this->assertStringContainsString('unpkg.com/leaflet@1.9.4/dist/leaflet.js', $view);
        $this->assertStringContainsString('The parcel list remains available.', $view);
    }
}
