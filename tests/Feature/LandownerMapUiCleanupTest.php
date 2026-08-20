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

    public function test_landowner_mobile_context_controls_are_compact(): void
    {
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));
        $css = file_get_contents(resource_path('css/landowner-mobile-context-compact.css'));

        $this->assertStringContainsString("import '../css/landowner-mobile-context-compact.css';", $bootstrap);
        $this->assertStringContainsString('@media screen and (max-width: 1100px)', $css);
        $this->assertStringContainsString('.lo-topbar-right .onboarding-help-button', $css);
        $this->assertStringContainsString('.lo-topbar-right .lo-access-chip', $css);
        $this->assertStringContainsString('height: 34px !important;', $css);
    }
}
