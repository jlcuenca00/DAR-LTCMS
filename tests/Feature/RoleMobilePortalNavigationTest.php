<?php

namespace Tests\Feature;

use Tests\TestCase;

class RoleMobilePortalNavigationTest extends TestCase
{
    public function test_role_portals_use_the_canonical_responsive_controller(): void
    {
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));
        $script = file_get_contents(resource_path('js/responsive-hardening.js'));
        $css = file_get_contents(resource_path('css/responsive-hardening.css'));

        $this->assertStringContainsString("import './responsive-hardening';", $bootstrap);
        $this->assertStringContainsString("key: 'landowner'", $script);
        $this->assertStringContainsString("key: 'geodetic'", $script);
        $this->assertStringContainsString('.dar-mobile-portal-header', $css);
        $this->assertStringContainsString('@media screen and (max-width: 1100px)', $css);
    }

    public function test_landowner_mobile_navigation_only_contains_landowner_destinations(): void
    {
        $script = file_get_contents(resource_path('js/responsive-hardening.js'));
        $landownerStart = strpos($script, "key: 'landowner'");
        $geodeticStart = strpos($script, "key: 'geodetic'");

        $this->assertNotFalse($landownerStart);
        $this->assertNotFalse($geodeticStart);

        $landownerConfig = substr($script, $landownerStart, $geodeticStart - $landownerStart);

        $this->assertStringContainsString("source: 'Dashboard'", $landownerConfig);
        $this->assertStringContainsString("source: 'My Parcel Map'", $landownerConfig);
        $this->assertStringContainsString("source: 'My Parcel Records'", $landownerConfig);
        $this->assertStringContainsString("source: 'My Applications'", $landownerConfig);
        $this->assertStringNotContainsString('/staff/users', $landownerConfig);
    }

    public function test_geodetic_mobile_navigation_stays_within_mapping_and_reference_access(): void
    {
        $script = file_get_contents(resource_path('js/responsive-hardening.js'));
        $geodeticStart = strpos($script, "key: 'geodetic'");
        $configEnd = strpos($script, '];', $geodeticStart);

        $this->assertNotFalse($geodeticStart);
        $this->assertNotFalse($configEnd);

        $geodeticConfig = substr($script, $geodeticStart, $configEnd - $geodeticStart);

        $this->assertStringContainsString("source: 'Dashboard'", $geodeticConfig);
        $this->assertStringContainsString("source: 'Parcel Map'", $geodeticConfig);
        $this->assertStringContainsString("source: 'Parcel References'", $geodeticConfig);
        $this->assertStringNotContainsString('Applications', $geodeticConfig);
        $this->assertStringNotContainsString('User Management', $geodeticConfig);
    }

    public function test_profile_remains_rightmost_in_role_mobile_headers(): void
    {
        $script = file_get_contents(resource_path('js/responsive-hardening.js'));

        $notificationAppend = strpos($script, 'controls.appendChild(notification)');
        $accountAppend = strpos($script, 'controls.appendChild(account)');

        $this->assertNotFalse($notificationAppend);
        $this->assertNotFalse($accountAppend);
        $this->assertLessThan($accountAppend, $notificationAppend);
        $this->assertStringContainsString('profile/avatar remains the rightmost control', $script);
    }

    public function test_role_mobile_account_menu_is_bounded_near_the_top_controls(): void
    {
        $css = file_get_contents(resource_path('css/responsive-hardening.css'));

        $this->assertStringContainsString('html body .dar-mobile-portal-header .account-menu-panel', $css);
        $this->assertStringContainsString("top: calc(var(--dar-safe-top) + 60px) !important;", $css);
        $this->assertStringContainsString('right: max(8px, var(--dar-safe-right)) !important;', $css);
        $this->assertStringContainsString('left: auto !important;', $css);
        $this->assertStringContainsString('max-height: calc(100dvh', $css);
    }

    public function test_sidebar_remains_available_if_mobile_enhancement_does_not_initialize(): void
    {
        $css = file_get_contents(resource_path('css/responsive-hardening.css'));
        $script = file_get_contents(resource_path('js/responsive-hardening.js'));

        $this->assertStringContainsString(':not(.dar-responsive-ready)', $css);
        $this->assertStringContainsString("shell.classList.add('dar-responsive-ready')", $script);
    }
}
