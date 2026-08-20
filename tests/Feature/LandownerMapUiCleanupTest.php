<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandownerMapUiCleanupTest extends TestCase
{
    public function test_landowner_map_does_not_render_a_single_state_legend(): void
    {
        $view = file_get_contents(resource_path('views/landowner/maps/parcel-map.blade.php'));

        $this->assertStringNotContainsString('>Legend<', $view);
        $this->assertStringNotContainsString('Your mapped parcel record', $view);
        $this->assertStringNotContainsString('lo-legend-list', $view);
    }

    public function test_landowner_mobile_context_is_governed_by_canonical_responsive_contract(): void
    {
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));
        $script = file_get_contents(resource_path('js/responsive-hardening.js'));
        $css = file_get_contents(resource_path('css/responsive-hardening.css'));

        $this->assertStringContainsString("import './responsive-hardening';", $bootstrap);
        $this->assertStringNotContainsString("import '../css/landowner-mobile-context-compact.css';", $bootstrap);
        $this->assertStringContainsString("key: 'landowner'", $script);
        $this->assertStringContainsString('.dar-mobile-portal-nav.landowner', $css);
        $this->assertStringContainsString('--dar-touch-target: 44px;', $css);
        $this->assertStringContainsString('@media screen and (max-width: 1100px)', $css);
    }
}
