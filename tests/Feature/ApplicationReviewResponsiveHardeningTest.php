<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationReviewResponsiveHardeningTest extends TestCase
{
    public function test_application_review_rail_becomes_in_flow_on_compact_viewports(): void
    {
        $css = file_get_contents(resource_path('css/responsive-hardening.css'));

        $this->assertStringContainsString('.application-review-page .requirement-rail {', $css);
        $this->assertStringContainsString('position: static !important;', $css);
        $this->assertStringContainsString('pointer-events: auto !important;', $css);
        $this->assertStringContainsString('.application-review-page .requirement-rail-scroll', $css);
        $this->assertStringContainsString('max-height: min(36dvh, 320px) !important;', $css);
    }

    public function test_application_review_uses_integrated_workflow_panel_and_compact_topbar_control(): void
    {
        $view = file_get_contents(resource_path('views/staff/applications/show.blade.php'));

        $this->assertStringContainsString('id="workflow-overview"', $view);
        $this->assertStringContainsString('id="workflow-topbar-control"', $view);
        $this->assertStringContainsString('data-workflow-modal-open', $view);
        $this->assertStringContainsString('IntersectionObserver', $view);
        $this->assertStringContainsString("staffTopbar?.classList.toggle('has-workflow-sticky', scrolledPast)", $view);
        $this->assertStringNotContainsString('workflow-fab', $view);
    }

    public function test_application_review_modals_are_dynamic_viewport_bounded(): void
    {
        $css = file_get_contents(resource_path('css/responsive-hardening.css'));

        $this->assertStringContainsString('.application-review-page .workflow-modal-card', $css);
        $this->assertStringContainsString('.application-review-page .decision-modal-card', $css);
        $this->assertStringContainsString('max-height: calc(100dvh', $css);
        $this->assertStringContainsString('.application-review-page .decision-modal-actions', $css);
        $this->assertStringContainsString('grid-template-columns: 1fr !important;', $css);
    }

    public function test_responsive_work_does_not_modify_final_decision_lock_language(): void
    {
        $view = file_get_contents(resource_path('views/staff/applications/show.blade.php'));

        $this->assertStringContainsString('Final Decision Locked', $view);
        $this->assertStringContainsString('Uploads, document removals, resubmission,', $view);
        $this->assertStringContainsString('and further decision changes are locked for audit integrity.', $view);
        $this->assertStringContainsString('Authorized release tracking remains available and does not change the final decision.', $view);
    }
}
