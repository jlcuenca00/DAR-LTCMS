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
        $this->assertStringContainsString('notificationPlaceholder.after(notification)', $script);
        $this->assertStringContainsString('accountPlaceholder.after(account)', $script);
    }

    public function test_landowner_and_geodetic_access_scope_moves_into_the_mobile_topbar(): void
    {
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));
        $polish = file_get_contents(resource_path('js/mobile-portal-polish.js'));
        $css = file_get_contents(resource_path('css/mobile-portal-polish.css'));

        $this->assertStringContainsString("import './mobile-portal-polish';", $bootstrap);
        $this->assertStringContainsString("chip: '.lo-access-chip'", $polish);
        $this->assertStringContainsString("chip: '.geo-access-chip'", $polish);
        $this->assertStringContainsString('mobileActions.insertBefore(chip, account)', $polish);
        $this->assertStringContainsString('chipPlaceholder.after(chip)', $polish);
        $this->assertStringContainsString('.dar-mobile-portal-actions > :is(.lo-access-chip, .geo-access-chip)', $css);
        $this->assertStringContainsString('height: 32px !important;', $css);
        $this->assertStringContainsString('min-height: 32px !important;', $css);
        $this->assertStringContainsString("compactLabel: 'Own Only'", $polish);
        $this->assertStringContainsString("compactLabel: 'Read Only'", $polish);
    }

    public function test_landowner_tour_help_icon_moves_into_the_same_mobile_topbar(): void
    {
        $polish = file_get_contents(resource_path('js/mobile-portal-polish.js'));
        $css = file_get_contents(resource_path('css/mobile-portal-polish.css'));

        $this->assertStringContainsString("help: '[data-onboarding-help=\"landowner_portal\"]'", $polish);
        $this->assertStringContainsString('mobileActions.insertBefore(help, notification)', $polish);
        $this->assertStringContainsString('helpPlaceholder.after(help)', $polish);
        $this->assertStringContainsString('.dar-mobile-portal-actions > .onboarding-help-button', $css);
    }

    public function test_obsolete_development_notice_is_not_loaded_into_authenticated_portals(): void
    {
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));

        $this->assertStringNotContainsString("import './development-notice';", $bootstrap);
        $this->assertStringNotContainsString("import '../css/development-notice.css';", $bootstrap);
    }

    public function test_landowner_and_geodetic_mobile_record_cards_do_not_require_horizontal_scrolling(): void
    {
        $css = file_get_contents(resource_path('css/mobile-portal-polish.css'));

        $this->assertStringContainsString('.lo-shell .lo-parcel-table-wrap', $css);
        $this->assertStringContainsString('.geo-shell .geo-record-table-wrap', $css);
        $this->assertStringContainsString('overflow-x: hidden !important;', $css);
        $this->assertStringContainsString('.lo-reference-list', $css);
        $this->assertStringContainsString('.geo-reference-list', $css);
        $this->assertStringContainsString('white-space: normal !important;', $css);
        $this->assertStringContainsString('grid-template-columns: minmax(48px, auto) minmax(0, 1fr) !important;', $css);
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
