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
        $this->assertStringContainsString('enhanceRequirementGroupHeaders', $lastMile);
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

    public function test_staff_dashboard_uses_actionable_attention_groups_instead_of_oldest_record_metrics(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Staff/StaffDashboardController.php'));
        $dashboard = file_get_contents(resource_path('views/dashboards/staff.blade.php'));

        $this->assertStringContainsString('Incomplete Requirements', $controller);
        $this->assertStringContainsString('Requirements Complete', $controller);
        $this->assertStringContainsString('No Update for More Than 7 Days', $controller);
        $this->assertStringContainsString("['attention' => 'missing_requirements']", $controller);
        $this->assertStringContainsString("['attention' => 'requirements_complete']", $controller);
        $this->assertStringContainsString("['attention' => 'stale']", $controller);
        $this->assertStringNotContainsString('Oldest Legal Review', $controller);
        $this->assertStringNotContainsString('Oldest For Releasing', $controller);

        $this->assertStringContainsString('Actionable groups for requirement work and follow-up.', $dashboard);
        $this->assertStringContainsString('attention-item', $dashboard);
        $this->assertStringContainsString('Clear attention filter', $dashboard);
    }

    public function test_open_requirements_targets_and_reveals_the_first_incomplete_required_entry(): void
    {
        $intakeFlow = file_get_contents(resource_path('js/application-intake-flow.js'));

        $this->assertStringContainsString('firstRequirementNeedingAttention = missingBlockingCards[0] || firstRequirement', $intakeFlow);
        $this->assertStringContainsString('revealApplicationReviewTarget', $intakeFlow);
        $this->assertStringContainsString("requirementGroup.classList.remove('is-collapsed')", $intakeFlow);
        $this->assertStringContainsString("toggle?.setAttribute('aria-expanded', 'true')", $intakeFlow);
        $this->assertStringContainsString("target.focus({ preventScroll: true })", $intakeFlow);
    }

    public function test_requirement_group_headers_replace_expand_buttons_with_accessible_card_toggles(): void
    {
        $lastMileJs = file_get_contents(resource_path('js/ui-ux-last-mile.js'));
        $lastMileCss = file_get_contents(resource_path('css/ui-ux-last-mile.css'));

        $this->assertStringContainsString('enhanceRequirementGroupHeaders', $lastMileJs);
        $this->assertStringContainsString("legacyToggle?.remove()", $lastMileJs);
        $this->assertStringContainsString("header.setAttribute('role', 'button')", $lastMileJs);
        $this->assertStringContainsString("header.setAttribute('tabindex', '0')", $lastMileJs);
        $this->assertStringContainsString("header.setAttribute('aria-controls', body.id)", $lastMileJs);
        $this->assertStringContainsString("event.key !== 'Enter' && event.key !== ' '", $lastMileJs);
        $this->assertStringContainsString('.ui-requirement-group-chevron', $lastMileCss);
        $this->assertStringContainsString('.requirement-group-panel:not(.is-collapsed) .ui-requirement-group-chevron i', $lastMileCss);
    }

    public function test_dashboard_and_application_source_rows_use_shared_clickable_row_navigation(): void
    {
        $rowNavigation = file_get_contents(resource_path('js/staff-record-row-navigation.js'));

        $this->assertStringContainsString('enhanceDashboardApplications', $rowNavigation);
        $this->assertStringContainsString("if (path === '/staff/dashboard') enhanceDashboardApplications();", $rowNavigation);
        $this->assertStringContainsString('enhanceMatchedApplicationSources', $rowNavigation);
        $this->assertStringContainsString("/^\\/staff\\/applications\\/[^/]+$/", $rowNavigation);
        $this->assertStringContainsString('removeActionColumn(table)', $rowNavigation);
        $this->assertStringContainsString('makeRowNavigable(row, href', $rowNavigation);
    }

    public function test_mobile_parcel_search_and_leaflet_attribution_are_kept_compact(): void
    {
        $lastMile = file_get_contents(resource_path('css/ui-ux-last-mile.css'));

        $this->assertStringContainsString('#parcel-map-search.parcel-search-input', $lastMile);
        $this->assertStringContainsString('max-height: 44px !important', $lastMile);
        $this->assertStringContainsString('#parcel-map .leaflet-control-attribution', $lastMile);
        $this->assertStringContainsString('white-space: nowrap !important', $lastMile);
        $this->assertStringContainsString('max-width: none !important', $lastMile);
    }
}
