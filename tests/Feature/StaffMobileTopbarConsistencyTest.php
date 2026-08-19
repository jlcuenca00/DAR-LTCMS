<?php

namespace Tests\Feature;

use Tests\TestCase;

class StaffMobileTopbarConsistencyTest extends TestCase
{
    public function test_staff_mobile_topbar_keeps_bell_and_account_on_one_row(): void
    {
        $component = file_get_contents(resource_path('views/components/account-menu.blade.php'));

        $this->assertIsString($component);
        $this->assertStringContainsString('@media screen and (max-width: 640px)', $component);
        $this->assertStringContainsString('grid-template-columns: 44px minmax(0, 1fr) auto !important;', $component);
        $this->assertStringContainsString('.staff-topbar-actions > .notification-dropdown', $component);
        $this->assertStringContainsString('.staff-topbar-actions > .account-topbar-cluster', $component);
        $this->assertStringContainsString('grid-column: 3 !important;', $component);
    }

    public function test_staff_mobile_shell_preserves_sixteen_pixel_edge_gutters(): void
    {
        $component = file_get_contents(resource_path('views/components/account-menu.blade.php'));

        $this->assertIsString($component);
        $this->assertStringContainsString('padding-left: 16px !important;', $component);
        $this->assertStringContainsString('padding-right: 16px !important;', $component);
        $this->assertStringContainsString('.staff-content', $component);
    }
}
