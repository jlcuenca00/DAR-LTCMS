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

    public function test_application_review_workflow_action_stops_floating_on_compact_viewports(): void
    {
        $responsiveCss = file_get_contents(resource_path('css/responsive-hardening.css'));
        $lastMileCss = file_get_contents(resource_path('css/ui-ux-last-mile.css'));

        $this->assertStringContainsString('.application-review-page .workflow-fab {', $responsiveCss);
        $this->assertStringContainsString('position: static !important;', $responsiveCss);
        $this->assertStringContainsString('width: 100% !important;', $responsiveCss);

        // The trigger is rendered immediately after the application-review wrapper, so it also
        // needs an unscoped compact rule to prevent the fixed desktop FAB from covering fields.
        $this->assertStringContainsString('@media (max-width: 1100px)', $lastMileCss);
        $this->assertStringContainsString('.workflow-fab {', $lastMileCss);
        $this->assertStringContainsString('inset: auto !important;', $lastMileCss);
        $this->assertStringContainsString('margin: 4px 0 24px !important;', $lastMileCss);
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
        $this->assertStringContainsString('and denial actions are locked for audit integrity.', $view);
    }
}
