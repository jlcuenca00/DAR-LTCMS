<?php

namespace Tests\Feature;

use Tests\TestCase;

class RoleMobilePortalNavigationTest extends TestCase
{
    public function test_role_mobile_portal_assets_are_loaded(): void
    {
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));

        $this->assertStringContainsString("import './role-mobile-portal-nav';", $bootstrap);
        $this->assertStringContainsString("import '../css/role-mobile-portal-nav.css';", $bootstrap);
    }

    public function test_role_top_navigation_is_limited_to_tablet_and_phone_widths(): void
    {
        $script = file_get_contents(resource_path('js/role-mobile-portal-nav.js'));
        $css = file_get_contents(resource_path('css/role-mobile-portal-nav.css'));

        $this->assertStringContainsString("matchMedia('(max-width: 1100px)')", $script);
        $this->assertStringContainsString('.role-mobile-portal-header {', $css);
        $this->assertStringContainsString('display: none;', $css);
        $this->assertStringContainsString('@media screen and (max-width: 1100px)', $css);
        $this->assertStringContainsString('.lo-shell > .lo-sidebar', $css);
        $this->assertStringContainsString('.geo-shell > .geo-sidebar', $css);
    }

    public function test_landowner_mobile_navigation_only_contains_landowner_destinations(): void
    {
        $script = file_get_contents(resource_path('js/role-mobile-portal-nav.js'));

        $this->assertStringContainsString("portalKey: 'landowner'", $script);
        $this->assertStringContainsString("sourceLabel: 'Dashboard'", $script);
        $this->assertStringContainsString("sourceLabel: 'My Parcel Map'", $script);
        $this->assertStringContainsString("sourceLabel: 'My Parcel Records'", $script);
        $this->assertStringContainsString("sourceLabel: 'My Applications'", $script);
        $this->assertStringNotContainsString("'/staff/users'", $script);
    }

    public function test_geodetic_mobile_navigation_stays_within_mapping_and_reference_access(): void
    {
        $script = file_get_contents(resource_path('js/role-mobile-portal-nav.js'));

        $this->assertStringContainsString("portalKey: 'geodetic'", $script);
        $this->assertStringContainsString("sourceLabel: 'Parcel Map'", $script);
        $this->assertStringContainsString("sourceLabel: 'Parcel References'", $script);
        $this->assertStringNotContainsString('Applications', substr($script, strpos($script, "portalKey: 'geodetic'")));
    }

    public function test_profile_is_appended_after_notifications_for_role_mobile_headers(): void
    {
        $script = file_get_contents(resource_path('js/role-mobile-portal-nav.js'));

        $notificationAppend = strpos($script, 'controlCluster.appendChild(notification)');
        $accountAppend = strpos($script, 'controlCluster.appendChild(accountCluster)');

        $this->assertNotFalse($notificationAppend);
        $this->assertNotFalse($accountAppend);
        $this->assertLessThan($accountAppend, $notificationAppend);
    }

    public function test_role_mobile_account_menu_is_top_right_not_bottom_sheet(): void
    {
        $css = file_get_contents(resource_path('css/role-mobile-portal-nav.css'));

        $this->assertStringContainsString('html body .role-mobile-portal-header .account-menu-panel', $css);
        $this->assertStringContainsString('top: 60px !important;', $css);
        $this->assertStringContainsString('right: 8px !important;', $css);
        $this->assertStringContainsString('bottom: auto !important;', $css);
    }
}
