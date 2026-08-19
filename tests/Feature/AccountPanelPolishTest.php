<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccountPanelPolishTest extends TestCase
{
    public function test_account_panel_polish_asset_is_loaded(): void
    {
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));

        $this->assertIsString($bootstrap);
        $this->assertStringContainsString("import '../css/account-panel-polish.css';", $bootstrap);
    }

    public function test_account_icons_and_chevrons_are_centered_against_full_rows(): void
    {
        $css = file_get_contents(resource_path('css/account-panel-polish.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('.account-panel-action > .account-panel-icon', $css);
        $this->assertStringContainsString('align-self: center !important;', $css);
        $this->assertStringContainsString('.account-panel-action > .account-panel-chevron', $css);
    }

    public function test_reduce_motion_uses_accessible_switch_semantics_and_standard_toggle_proportions(): void
    {
        $script = file_get_contents(resource_path('js/account-panel.js'));
        $css = file_get_contents(resource_path('css/account-panel-polish.css'));

        $this->assertIsString($script);
        $this->assertIsString($css);
        $this->assertStringContainsString("control.setAttribute('role', 'switch');", $script);
        $this->assertStringContainsString('width: 44px !important;', $css);
        $this->assertStringContainsString('height: 24px !important;', $css);
        $this->assertStringContainsString('width: 20px !important;', $css);
        $this->assertStringContainsString('top: 2px !important;', $css);
        $this->assertStringContainsString('left: 2px !important;', $css);
    }
}
