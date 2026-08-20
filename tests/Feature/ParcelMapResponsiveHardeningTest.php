<?php

namespace Tests\Feature;

use Tests\TestCase;

class ParcelMapResponsiveHardeningTest extends TestCase
{
    public function test_all_portal_maps_use_bundled_leaflet_instead_of_duplicate_cdn_assets(): void
    {
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));
        $views = [
            file_get_contents(resource_path('views/staff/maps/parcel-map.blade.php')),
            file_get_contents(resource_path('views/landowner/maps/parcel-map.blade.php')),
            file_get_contents(resource_path('views/geodetic/maps/parcel-map.blade.php')),
        ];

        $this->assertStringContainsString("import L from 'leaflet';", $bootstrap);
        $this->assertStringContainsString("import 'leaflet/dist/leaflet.css';", $bootstrap);

        foreach ($views as $view) {
            $this->assertStringContainsString('typeof window.L', $view);
            $this->assertStringNotContainsString('unpkg.com/leaflet@1.9.4', $view);
            $this->assertStringNotContainsString('cdn.jsdelivr.net/npm/leaflet@1.9.4', $view);
        }
    }

    public function test_all_portal_maps_use_dynamic_viewport_height_rules(): void
    {
        $staff = file_get_contents(resource_path('views/staff/maps/parcel-map.blade.php'));
        $landowner = file_get_contents(resource_path('views/landowner/maps/parcel-map.blade.php'));
        $geodetic = file_get_contents(resource_path('views/geodetic/maps/parcel-map.blade.php'));

        $this->assertStringContainsString('calc(100dvh - 212px)', $staff);
        $this->assertStringContainsString('calc(100dvh - 180px)', $landowner);
        $this->assertStringContainsString('calc(100dvh - 180px)', $geodetic);

        $this->assertStringNotContainsString('calc(100vh - 212px)', $staff);
        $this->assertStringNotContainsString('calc(100vh - 180px)', $landowner);
        $this->assertStringNotContainsString('calc(100vh - 180px)', $geodetic);
    }

    public function test_map_search_and_tool_controls_are_touch_sized(): void
    {
        $staff = file_get_contents(resource_path('views/staff/maps/parcel-map.blade.php'));
        $landowner = file_get_contents(resource_path('views/landowner/maps/parcel-map.blade.php'));
        $geodetic = file_get_contents(resource_path('views/geodetic/maps/parcel-map.blade.php'));

        $this->assertStringContainsString('.parcel-search-result { width: 100%; min-height: 44px;', $staff);
        $this->assertStringContainsString('.lo-search-result { width: 100%; min-height: 44px;', $landowner);
        $this->assertStringContainsString('min-height: 44px;', $geodetic);
        $this->assertStringContainsString('@media (pointer: coarse)', $staff);
        $this->assertStringContainsString('@media (pointer: coarse)', $landowner);
        $this->assertStringContainsString('@media (pointer: coarse)', $geodetic);
    }
}
