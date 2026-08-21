<?php

namespace Tests\Feature;

use Tests\TestCase;

class ClearancePrintReferenceTest extends TestCase
{
    public function test_clearance_show_routes_render_fast_html_print_views_instead_of_redirecting_to_dompdf(): void
    {
        $staffController = file_get_contents(app_path('Http/Controllers/Staff/ApplicationClearanceController.php'));
        $landownerController = file_get_contents(app_path('Http/Controllers/Landowner/ApplicationClearanceController.php'));

        foreach ([$staffController, $landownerController] as $controller) {
            $this->assertStringContainsString("return view('staff.clearances.show'", $controller);
            $this->assertStringContainsString("'clearance', 'documents'", $controller);
            $this->assertStringContainsString("'isRemoteEnabled' => false", $controller);
            $this->assertStringContainsString("'defaultFont' => 'Helvetica'", $controller);
            $this->assertStringContainsString('612, 936', $controller);
        }

        $this->assertStringNotContainsString(
            "return redirect()->route('staff.applications.clearance.pdf'",
            $staffController
        );

        $this->assertStringNotContainsString(
            "return redirect()->route('landowner.applications.clearance.pdf'",
            $landownerController
        );
    }

    public function test_clearance_print_view_uses_local_assets_and_folio_reference_layout(): void
    {
        $show = file_get_contents(resource_path('views/staff/clearances/show.blade.php'));
        $styles = file_get_contents(resource_path('views/staff/clearances/partials/form5-reference-styles.blade.php'));
        $form = file_get_contents(resource_path('views/staff/clearances/partials/form5-content.blade.php'));
        $pdf = file_get_contents(resource_path('views/staff/clearances/pdf.blade.php'));

        $this->assertStringContainsString("asset('images/dar-logo.svg')", $show);
        $this->assertStringContainsString("asset('images/bagong-pilipinas.png')", $show);
        $this->assertStringContainsString('form5-reference-styles', $show);
        $this->assertStringNotContainsString("style.removeProperty('font-family')", $show);

        $this->assertStringContainsString('size: 8.5in 13in', $styles);
        $this->assertStringContainsString('width: 816px', $styles);
        $this->assertStringContainsString('.official-header', $styles);
        $this->assertStringContainsString('.ltc-number', $styles);
        $this->assertStringContainsString('.decision-box', $styles);
        $this->assertStringContainsString('.green-bars', $styles);
        $this->assertStringContainsString('.footer', $styles);

        $this->assertStringContainsString('size: 8.5in 13in', $form);
        $this->assertStringContainsString("public_path('images/' . \$filename)", $form);
        $this->assertStringContainsString("\$logoAsset('dar-logo.svg')", $form);
        $this->assertStringContainsString("\$logoAsset('bagong-pilipinas.png')", $form);
        $this->assertStringNotContainsString('raw.githubusercontent.com', $form);
        $this->assertStringContainsString('CERTIFICATION', $form);
        $this->assertStringContainsString('(Land Transfer Clearance)', $form);
        $this->assertStringContainsString('ENGR. MANUEL M. GALON, JR.', $form);
        $this->assertStringContainsString('Not official if not sealed', $form);

        $this->assertStringContainsString('size: 8.5in 13in', $pdf);
        $this->assertStringContainsString("'pdfMode' => true", $pdf);
        $this->assertStringNotContainsString('Storage::disk', $pdf);
    }
}
