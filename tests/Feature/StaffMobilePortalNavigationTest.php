<?php

namespace Tests\Feature;

use Tests\TestCase;

class StaffMobilePortalNavigationTest extends TestCase
{
    public function test_staff_mobile_portal_navigation_assets_are_loaded(): void
    {
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));

        $this->assertStringContainsString("import './staff-mobile-portal-nav';", $bootstrap);
        $this->assertStringContainsString("import '../css/staff-mobile-portal-nav.css';", $bootstrap);
        $this->assertStringContainsString("import '../css/staff-mobile-account-menu-position.css';", $bootstrap);
    }

    public function test_mobile_portal_navigation_is_limited_to_tablet_and_phone_widths(): void
    {
        $script = file_get_contents(resource_path('js/staff-mobile-portal-nav.js'));
        $css = file_get_contents(resource_path('css/staff-mobile-portal-nav.css'));

        $this->assertStringContainsString("matchMedia('(max-width: 1100px)')", $script);
        $this->assertStringContainsString('.staff-mobile-portal-header {', $css);
        $this->assertStringContainsString('display: none;', $css);
        $this->assertStringContainsString('@media screen and (max-width: 1100px)', $css);
        $this->assertStringContainsString('html body .staff-shell > .staff-sidebar', $css);
        $this->assertStringContainsString('display: none !important;', $css);
    }

    public function test_mobile_portal_navigation_contains_the_primary_staff_modules(): void
    {
        $script = file_get_contents(resource_path('js/staff-mobile-portal-nav.js'));

        $this->assertStringContainsString("displayLabel: 'Dashboard'", $script);
        $this->assertStringContainsString("displayLabel: 'Applications'", $script);
        $this->assertStringContainsString("displayLabel: 'Landowners'", $script);
        $this->assertStringContainsString("displayLabel: 'Parcels'", $script);
        $this->assertStringContainsString('<span>More</span>', $script);
        $this->assertStringContainsString("{ label: 'Parcel Map'", $script);
        $this->assertStringContainsString("{ label: 'Source Records'", $script);
        $this->assertStringContainsString("{ label: 'Monitoring Reports'", $script);
        $this->assertStringContainsString("{ label: 'Audit Logs'", $script);
        $this->assertStringContainsString('User Management', $script);
    }

    public function test_profile_is_appended_after_notifications_in_the_mobile_header(): void
    {
        $script = file_get_contents(resource_path('js/staff-mobile-portal-nav.js'));

        $notificationAppend = strpos($script, 'controlCluster.appendChild(notification)');
        $accountAppend = strpos($script, 'controlCluster.appendChild(accountCluster)');

        $this->assertNotFalse($notificationAppend);
        $this->assertNotFalse($accountAppend);
        $this->assertLessThan($accountAppend, $notificationAppend);
    }

    public function test_phone_account_menu_stays_near_the_profile_control_instead_of_becoming_a_bottom_sheet(): void
    {
        $css = file_get_contents(resource_path('css/staff-mobile-account-menu-position.css'));

        $this->assertStringContainsString('@media screen and (max-width: 640px)', $css);
        $this->assertStringContainsString('top: 60px !important;', $css);
        $this->assertStringContainsString('right: 8px !important;', $css);
        $this->assertStringContainsString('bottom: auto !important;', $css);
        $this->assertStringContainsString('left: auto !important;', $css);
    }
}
