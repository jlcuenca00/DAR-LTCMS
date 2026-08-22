<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocumentationAlignmentTest extends TestCase
{
    /** @var array<int, string> */
    private array $canonicalDocuments = [
        'README.md',
        'docs/FINAL_SYSTEM_BASELINE.md',
        'docs/thesis-documentation-alignment.md',
        'docs/barebones-tester-handoff.md',
        'docs/final-barebones-release-checklist.md',
        'docs/tester-data-entry-guide.md',
        'docs/final-manual-testing-checklist.md',
        'docs/final-defense-screenshot-checklist.md',
        'docs/RELEASE_PREPARATION.md',
        'ISO_IEC_25010_2023_SYSTEM_READINESS.md',
    ];

    public function test_canonical_documents_use_dar_ltcms_identity_only(): void
    {
        foreach ($this->canonicalDocuments as $path) {
            $content = $this->read($path);

            $this->assertStringNotContainsString(
                'DAR-iLAND',
                $content,
                "{$path} still contains obsolete DAR-iLAND branding."
            );
        }
    }

    public function test_current_user_and_tester_guides_use_released_and_denied_final_states(): void
    {
        $paths = [
            'docs/barebones-tester-handoff.md',
            'docs/final-barebones-release-checklist.md',
            'docs/tester-data-entry-guide.md',
            'docs/final-manual-testing-checklist.md',
            'docs/final-defense-screenshot-checklist.md',
        ];

        foreach ($paths as $path) {
            $content = $this->read($path);

            $this->assertStringContainsString('Released', $content, "{$path} must document Released.");
            $this->assertStringContainsString('Denied', $content, "{$path} must document Denied.");
            $this->assertDoesNotMatchRegularExpression(
                '/(^|\n)\s*[-*]\s+approved\s*$/im',
                $content,
                "{$path} must not present Approved as a current final workflow option."
            );
            $this->assertDoesNotMatchRegularExpression(
                '/(^|\n)\s*[-*]\s+not[ _-]?approved\s*$/im',
                $content,
                "{$path} must not present Not Approved as a current final workflow option."
            );
        }
    }

    public function test_final_baseline_preserves_clearance_only_scope_and_roles(): void
    {
        $baseline = $this->read('docs/FINAL_SYSTEM_BASELINE.md');

        $this->assertStringContainsString('DAR Negros Oriental Provincial Office', $baseline);
        $this->assertStringContainsString('manually encode applications', $baseline);
        $this->assertStringContainsString('Landowners', $baseline);
        $this->assertStringContainsString('limited/read-only', $baseline);
        $this->assertStringContainsString('does **not** mean the platform has', $baseline);
        $this->assertStringContainsString('transferred legal ownership', $baseline);
        $this->assertStringContainsString('registry alteration', $baseline);
        $this->assertStringContainsString('Released', $baseline);
        $this->assertStringContainsString('Denied', $baseline);
        $this->assertStringContainsString('editing is locked', $baseline);
        $this->assertStringContainsString('supporting-document upload/removal is locked', $baseline);
    }

    public function test_tester_documentation_matches_current_seed_and_requirement_fields(): void
    {
        $handoff = $this->read('docs/barebones-tester-handoff.md');
        $reset = $this->read('docs/final-barebones-release-checklist.md');
        $entry = $this->read('docs/tester-data-entry-guide.md');

        $this->assertStringContainsString('five active Staff tester accounts', $handoff);
        $this->assertStringContainsString('5 Staff tester accounts', $reset);
        $this->assertStringContainsString('staff.tester@dar-ltcms.local', $handoff);
        $this->assertStringContainsString('Never run `migrate:fresh`', $reset);

        $this->assertStringContainsString('Date issued', $entry);
        $this->assertStringContainsString('Notarizer / lawyer name', $entry);
        $this->assertStringContainsString('Notarial Document No., Page No., Book No., and Series', $entry);
        $this->assertStringContainsString('requirement-specific fields', $entry);
    }

    public function test_form5_and_release_documentation_match_final_baseline(): void
    {
        $readme = $this->read('README.md');
        $baseline = $this->read('docs/FINAL_SYSTEM_BASELINE.md');
        $release = $this->read('docs/RELEASE_PREPARATION.md');

        foreach ([$readme, $baseline] as $content) {
            $this->assertStringContainsString('ENGR. MANUEL M. GALON, JR., OIC PARPO II', $content);
            $this->assertStringContainsString('8.5 x 13', $content);
            $this->assertStringContainsString('GRANTED', $content);
            $this->assertStringContainsString('DENIED', $content);
        }

        $this->assertStringContainsString('Release candidate / production validation pending', $readme);
        $this->assertStringContainsString('php artisan dar:release-check', $release);
        $this->assertStringContainsString('v1.0.0', $release);
        $this->assertStringContainsString('.release-commit', $release);
    }

    private function read(string $path): string
    {
        $fullPath = base_path($path);

        $this->assertFileExists($fullPath, "Required documentation file is missing: {$path}");

        return (string) file_get_contents($fullPath);
    }
}
