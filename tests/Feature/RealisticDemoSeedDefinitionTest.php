<?php

namespace Tests\Feature;

use Tests\TestCase;

class RealisticDemoSeedDefinitionTest extends TestCase
{
    private string $canonicalSeed = 'seed_mock_dumaguete_nonoverlap_agri_data.txt';

    public function test_canonical_demo_seed_uses_current_workflow_and_reference_values(): void
    {
        $sql = $this->read($this->canonicalSeed);

        $this->assertStringContainsString('2000.00', $sql);
        $this->assertStringContainsString("'Negros Oriental Province'", $sql);
        $this->assertStringContainsString("'private_agricultural'", $sql);
        $this->assertStringContainsString("'transferor'", $sql);
        $this->assertStringContainsString("'approved'", $sql);
        $this->assertStringContainsString("'denied'", $sql);
        $this->assertStringContainsString("'ready_for_release'", $sql);
        $this->assertStringContainsString("'released'", $sql);
        $this->assertStringContainsString('No ownership/registry mutation is simulated.', $sql);
        $this->assertStringContainsString('CREATE TEMP TABLE dar_demo_application_ids', $sql);
        $this->assertStringContainsString("'NOR-AGRI-'", $sql);
        $this->assertStringContainsString("'2026-NOR-DEMO-'", $sql);
        $this->assertStringNotContainsString("application_code LIKE '2026-DGT-%'", $sql);
        $this->assertStringNotContainsString("application_code LIKE '2026-NOR-%'", $sql);

        foreach ([
            'pending_review_legal',
            'endorsed_parpo_ii',
            "'TCT'",
            "'ROD Dumaguete'",
            "'Registered Owner'",
            "500.00",
        ] as $obsolete) {
            $this->assertStringNotContainsString(
                $obsolete,
                $sql,
                "Canonical demo seed still contains obsolete value: {$obsolete}"
            );
        }
    }

    public function test_demo_parcel_definitions_are_agricultural_closed_and_non_overlapping(): void
    {
        $rows = $this->datasetRows();

        $this->assertCount(16, $rows);

        $allowedStatuses = [
            'pending_legal_review',
            'returned_for_compliance',
            'awaiting_payment',
            'endorsed_lti',
            'returned_to_legal',
            'legal_evaluation',
            'endorsed_chief_legal',
            'endorsed_parpo',
            'for_releasing',
            'approved',
            'denied',
        ];

        $allowedReleaseStatuses = ['not_ready', 'ready_for_release', 'released'];
        $allowedTitleTypes = ['tct', 'oct', 'untitled'];
        $allowedBarangays = ['Basak', 'Apolong', 'Baslay', 'Malongcay Dacu'];
        $allowedMunicipalities = ['San Jose', 'Valencia', 'Dauin'];

        foreach ($rows as $row) {
            $this->assertContains($row['status'], $allowedStatuses);
            $this->assertContains($row['release_status'], $allowedReleaseStatuses);
            $this->assertContains($row['title_type'], $allowedTitleTypes);
            $this->assertContains($row['barangay'], $allowedBarangays);
            $this->assertContains($row['municipality'], $allowedMunicipalities);
            $this->assertGreaterThanOrEqual(0.5, (float) $row['area']);
            $this->assertLessThanOrEqual(5.0, (float) $row['area']);

            $polygon = $row['poly'];
            $this->assertGreaterThanOrEqual(4, count($polygon));
            $this->assertSame($polygon[0], $polygon[count($polygon) - 1], 'GeoJSON polygon must be closed.');
        }

        $this->assertSame(
            ['Dauin', 'San Jose', 'Valencia'],
            collect($rows)->pluck('municipality')->unique()->sort()->values()->all()
        );

        foreach (['Balugo', 'Cantil-e', 'Cadawinonan', 'Batinguel'] as $oldUrbanBarangay) {
            $this->assertFalse(
                collect($rows)->contains(fn (array $row): bool => $row['barangay'] === $oldUrbanBarangay),
                "Old Dumaguete demo barangay {$oldUrbanBarangay} must not remain in the canonical dataset."
            );
        }

        for ($i = 0; $i < count($rows); $i++) {
            $a = $this->bounds($rows[$i]['poly']);

            for ($j = $i + 1; $j < count($rows); $j++) {
                $b = $this->bounds($rows[$j]['poly']);

                $xOverlap = min($a['max_x'], $b['max_x']) - max($a['min_x'], $b['min_x']);
                $yOverlap = min($a['max_y'], $b['max_y']) - max($a['min_y'], $b['min_y']);

                $this->assertFalse(
                    $xOverlap > 0 && $yOverlap > 0,
                    sprintf(
                        'Demo parcel bounding boxes overlap: %s and %s.',
                        $rows[$i]['seq'],
                        $rows[$j]['seq']
                    )
                );
            }
        }
    }

    public function test_demo_dataset_covers_the_current_application_lifecycle(): void
    {
        $rows = collect($this->datasetRows());

        foreach ([
            'pending_legal_review',
            'returned_for_compliance',
            'awaiting_payment',
            'endorsed_lti',
            'returned_to_legal',
            'legal_evaluation',
            'endorsed_chief_legal',
            'endorsed_parpo',
            'for_releasing',
            'approved',
            'denied',
        ] as $status) {
            $this->assertTrue(
                $rows->contains(fn (array $row): bool => $row['status'] === $status),
                "Demo dataset is missing workflow state {$status}."
            );
        }

        $this->assertTrue($rows->contains(
            fn (array $row): bool => $row['status'] === 'approved'
                && $row['release_status'] === 'not_ready'
        ));
        $this->assertTrue($rows->contains(
            fn (array $row): bool => $row['status'] === 'approved'
                && $row['release_status'] === 'ready_for_release'
        ));
        $this->assertTrue($rows->contains(
            fn (array $row): bool => $row['status'] === 'approved'
                && $row['release_status'] === 'released'
        ));
        $this->assertTrue($rows->contains(
            fn (array $row): bool => $row['status'] === 'denied'
                && $row['release_status'] === 'released'
        ));
    }

    public function test_legacy_mock_seed_entry_points_delegate_to_the_canonical_dataset(): void
    {
        foreach ([
            'seed_mock_dumaguete_agri_data.txt',
            'seed_mock_dumaguete_realistic_agri_data.txt',
        ] as $path) {
            $content = $this->read($path);

            $this->assertStringContainsString(
                '\\ir seed_mock_dumaguete_nonoverlap_agri_data.txt',
                $content
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function datasetRows(): array
    {
        $sql = $this->read($this->canonicalSeed);

        $matched = preg_match(
            "/jsonb_to_recordset\\('(.+?)'::jsonb\\)/s",
            $sql,
            $matches
        );

        $this->assertSame(1, $matched, 'Canonical demo dataset JSON could not be located.');

        return json_decode(
            str_replace("''", "'", $matches[1]),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    /** @param array<int, array{0: float|int, 1: float|int}> $polygon */
    private function bounds(array $polygon): array
    {
        $xs = array_column($polygon, 0);
        $ys = array_column($polygon, 1);

        return [
            'min_x' => min($xs),
            'min_y' => min($ys),
            'max_x' => max($xs),
            'max_y' => max($ys),
        ];
    }

    private function read(string $path): string
    {
        $fullPath = base_path($path);
        $this->assertFileExists($fullPath);

        return (string) file_get_contents($fullPath);
    }
}
