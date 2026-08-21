<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationModalViewportTest extends TestCase
{
    public function test_application_review_modals_are_portaled_to_the_viewport_root(): void
    {
        $javascript = file_get_contents(resource_path('js/ui-ux-last-mile.js'));
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));
        $css = file_get_contents(resource_path('css/application-modal-viewport.css'));

        $this->assertStringContainsString('portalApplicationReviewModals', $javascript);
        $this->assertStringContainsString("document.querySelectorAll('.workflow-modal-backdrop, .decision-modal-backdrop')", $javascript);
        $this->assertStringContainsString('document.body.appendChild(modal)', $javascript);
        $this->assertStringContainsString("modal.dataset.uiViewportPortal = 'true'", $javascript);

        $this->assertStringContainsString("import '../css/application-modal-viewport.css';", $bootstrap);
        $this->assertStringContainsString('html body > .workflow-modal-backdrop', $css);
        $this->assertStringContainsString('html body > .decision-modal-backdrop', $css);
        $this->assertStringContainsString('inset: 0 !important;', $css);
        $this->assertStringContainsString('height: 100dvh !important;', $css);
        $this->assertStringContainsString('z-index: 4000 !important;', $css);
    }
}
