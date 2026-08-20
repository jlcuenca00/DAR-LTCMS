<?php

namespace Tests\Feature;

use Tests\TestCase;

class UiUxSystemTest extends TestCase
{
    public function test_bootstrap_loads_unified_ui_ux_layers_after_responsive_hardening(): void
    {
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));

        $this->assertStringContainsString("import './responsive-hardening';", $bootstrap);
        $this->assertStringContainsString("import './ui-ux-system';", $bootstrap);
        $this->assertStringContainsString("import './ui-ux-last-mile';", $bootstrap);
        $this->assertStringContainsString("import '../css/ui-ux-system.css';", $bootstrap);
        $this->assertStringContainsString("import '../css/ui-ux-last-mile.css';", $bootstrap);

        $responsivePosition = strpos($bootstrap, "import './responsive-hardening';");
        $uiPosition = strpos($bootstrap, "import './ui-ux-system';");
        $this->assertNotFalse($responsivePosition);
        $this->assertNotFalse($uiPosition);
        $this->assertGreaterThan($responsivePosition, $uiPosition);
    }

    public function test_ui_ux_css_defines_consistent_controls_and_responsive_touch_contract(): void
    {
        $css = file_get_contents(resource_path('css/ui-ux-system.css'));
        $lastMile = file_get_contents(resource_path('css/ui-ux-last-mile.css'));

        $this->assertStringContainsString('--dar-control-height: 44px', $css);
        $this->assertStringContainsString('--dar-radius-control: 8px', $css);
        $this->assertStringContainsString('.ui-radio-group', $css);
        $this->assertStringContainsString('.ui-section-nav', $css);
        $this->assertStringContainsString('@media (pointer: coarse)', $css);
        $this->assertStringContainsString('min-height: 48px', $lastMile);
        $this->assertStringContainsString('.ui-review-disclosure-toggle', $lastMile);
        $this->assertStringContainsString('.ui-decision-scope-note', $lastMile);
        $this->assertStringContainsString('.requirement-rail-legend', $lastMile);
    }

    public function test_ui_ux_javascript_preserves_progressive_enhancement_and_semantics(): void
    {
        $system = file_get_contents(resource_path('js/ui-ux-system.js'));
        $lastMile = file_get_contents(resource_path('js/ui-ux-last-mile.js'));

        $this->assertStringContainsString('ensureLabelAssociations', $system);
        $this->assertStringContainsString("setAttribute('aria-current', 'page')", $system);
        $this->assertStringContainsString('selectToRadio', $system);
        $this->assertStringContainsString('syncConditionalFields', $system);
        $this->assertStringContainsString('addApplicationSectionNavigator', $system);
        $this->assertStringContainsString('enhanceReviewDisclosures', $system);
        $this->assertStringContainsString('enhanceUserRoleDisclosure', $system);
        $this->assertStringContainsString('event.stopImmediatePropagation()', $system);

        $this->assertStringContainsString('attachClientValidation', $lastMile);
        $this->assertStringContainsString('enhanceRecordSelects', $lastMile);
        $this->assertStringContainsString('addDecisionScopeBoundary', $lastMile);
        $this->assertStringContainsString('does not itself execute or finalize legal land ownership transfer or registry mutation', $lastMile);
    }

    public function test_notification_ui_keeps_approval_and_release_as_distinct_events(): void
    {
        $list = file_get_contents(resource_path('views/notifications/partials/list.blade.php'));
        $panel = file_get_contents(resource_path('views/notifications/partials/panel.blade.php'));

        foreach ([$list, $panel] as $view) {
            $this->assertStringContainsString('Application approved', $view);
            $this->assertStringContainsString('Clearance released', $view);
        }

        $this->assertStringNotContainsString("'application_approved' => 'Clearance released'", $list);
        $this->assertStringNotContainsString("'application_approved', 'application_released' => 'Clearance released'", $panel);
        $this->assertStringNotContainsString("'Approved' => 'Released'", $panel);
    }

    public function test_application_workflow_still_uses_existing_final_decision_routes(): void
    {
        $review = file_get_contents(resource_path('views/staff/applications/show.blade.php'));

        $this->assertStringContainsString("route('staff.applications.approve', \$application)", $review);
        $this->assertStringContainsString("route('staff.applications.not_approved', \$application)", $review);
        $this->assertStringContainsString('data-decision-confirm="release"', $review);
        $this->assertStringContainsString('data-decision-confirm="deny"', $review);
        $this->assertStringContainsString('final_decision_confirmation', $review);
    }
}
