<?php

namespace Tests\Feature;

use Tests\TestCase;

class StaffMobilePortalNavigationTest extends TestCase
{
    public function test_staff_mobile_portal_navigation_uses_canonical_assets(): void
    {
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));
        $script = file_get_contents(resource_path('js/responsive-hardening.js'));
        $css = file_get_contents(resource_path('css/responsive-hardening.css'));

        $this->assertStringContainsString("import './responsive-hardening';", $bootstrap);
        $this->assertStringContainsString("key: 'staff'", $script);
        $this->assertStringContainsString('.dar-mobile-portal-nav.staff', $css);

        $this->assertStringNotContainsString("import './staff-mobile-portal-nav';", $bootstrap);
        $this->assertStringNotContainsString("import '../css/staff-mobile-portal-nav.css';", $bootstrap);
        $this->assertStringNotContainsString("import '../css/staff-mobile-account-menu-position.css';", $bootstrap);
    }

    public function test_mobile_portal_navigation_is_limited_to_tablet_and_phone_widths(): void
    {
        $script = file_get_contents(resource_path('js/responsive-hardening.js'));
        $css = file_get_contents(resource_path('css/responsive-hardening.css'));

        $this->assertStringContainsString("matchMedia('(max-width: 1100px)')", $script);
        $this->assertStringContainsString('.dar-mobile-portal-header {', $css);
        $this->assertStringContainsString('display: none;', $css);
        $this->assertStringContainsString('@media screen and (max-width: 1100px)', $css);
        $this->assertStringContainsString('.dar-responsive-ready', $css);
    }

    public function test_mobile_portal_navigation_contains_primary_staff_modules(): void
    {
        $script = file_get_contents(resource_path('js/responsive-hardening.js'));
        $staffStart = strpos($script, "key: 'staff'");
        $landownerStart = strpos($script, "key: 'landowner'");

        $this->assertNotFalse($staffStart);
        $this->assertNotFalse($landownerStart);

        $staffConfig = substr($script, $staffStart, $landownerStart - $staffStart);

        $this->assertStringContainsString("label: 'Dashboard'", $staffConfig);
        $this->assertStringContainsString("label: 'Applications'", $staffConfig);
        $this->assertStringContainsString("label: 'Landowners'", $staffConfig);
        $this->assertStringContainsString("label: 'Parcels'", $staffConfig);
        $this->assertStringContainsString("source: 'Parcel Map'", $staffConfig);
        $this->assertStringContainsString("source: 'Source Records'", $staffConfig);
        $this->assertStringContainsString("source: 'Monitoring Reports'", $staffConfig);
        $this->assertStringContainsString("source: 'Audit Logs'", $staffConfig);
        $this->assertStringContainsString('User Management', $script);
    }

    public function test_profile_is_appended_after_notifications_in_mobile_header(): void
    {
        $script = file_get_contents(resource_path('js/responsive-hardening.js'));

        $notificationAppend = strpos($script, 'controls.appendChild(notification)');
        $accountAppend = strpos($script, 'controls.appendChild(account)');

        $this->assertNotFalse($notificationAppend);
        $this->assertNotFalse($accountAppend);
        $this->assertLessThan($accountAppend, $notificationAppend);
    }

    public function test_phone_account_menu_stays_near_profile_instead_of_becoming_bottom_sheet(): void
    {
        $css = file_get_contents(resource_path('css/responsive-hardening.css'));

        $this->assertStringContainsString('@media screen and (max-width: 760px)', $css);
        $this->assertStringContainsString('html body .dar-mobile-portal-header .account-menu-panel', $css);
        $this->assertStringContainsString('right: max(8px, var(--dar-safe-right)) !important;', $css);
        $this->assertStringContainsString('left: auto !important;', $css);
    }

    public function test_canonical_controller_blocks_competing_legacy_staff_hamburger(): void
    {
        $script = file_get_contents(resource_path('js/responsive-hardening.js'));

        $this->assertStringContainsString("sentinelClass: 'staff-mobile-nav-toggle'", $script);
        $this->assertStringContainsString('initResponsiveHardening();', $script);
        $this->assertStringContainsString("import('../css/responsive-hardening.css')", $script);
    }
}
