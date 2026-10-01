<?php

namespace Tests\Feature;

use App\Models\SourceRecordPackageImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InputSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_record_filters_reject_invalid_or_oversized_input(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.legacy-records.index', [
                'record_type' => 'not-a-real-record-type',
            ]))
            ->assertSessionHasErrors('record_type');

        $this->actingAs($staff)
            ->get(route('staff.legacy-records.index', [
                'search' => str_repeat('x', 256),
            ]))
            ->assertSessionHasErrors('search');
    }

    public function test_import_commit_rejects_rows_not_authorized_by_the_server_preview(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $batch = SourceRecordPackageImportBatch::create([
            'original_filename' => 'input-security.csv',
            'status' => 'previewed',
            'total_rows' => 1,
            'valid_rows' => 1,
            'error_rows' => 0,
            'duplicate_rows' => 0,
            'uploaded_by_user_id' => $staff->id,
            'preview_rows' => [[
                'row_index' => 2,
                'status' => 'valid',
                'possible_duplicate' => false,
                'errors' => [],
                'warnings' => [],
                'data' => [
                    'include_title' => false,
                    'include_landholding' => false,
                    'include_parcel_source' => false,
                    'include_historical_clearance' => false,
                ],
            ]],
            'summary' => [],
        ]);

        $this->actingAs($staff)
            ->from(route('staff.source-record-package-imports.preview', $batch))
            ->post(route('staff.source-record-package-imports.commit', $batch), [
                'selected_rows' => [999],
            ])
            ->assertRedirect(route('staff.source-record-package-imports.preview', $batch))
            ->assertSessionHasErrors('selected_rows');

        $this->assertSame('previewed', $batch->fresh()->status);
    }

    public function test_import_commit_requires_a_bounded_integer_row_array(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $batch = SourceRecordPackageImportBatch::create([
            'original_filename' => 'input-shape.csv',
            'status' => 'previewed',
            'total_rows' => 1,
            'valid_rows' => 1,
            'error_rows' => 0,
            'duplicate_rows' => 0,
            'uploaded_by_user_id' => $staff->id,
            'preview_rows' => [[
                'row_index' => 2,
                'status' => 'valid',
                'possible_duplicate' => false,
                'errors' => [],
                'warnings' => [],
                'data' => [],
            ]],
            'summary' => [],
        ]);

        $this->actingAs($staff)
            ->from(route('staff.source-record-package-imports.preview', $batch))
            ->post(route('staff.source-record-package-imports.commit', $batch), [
                'selected_rows' => '2',
            ])
            ->assertSessionHasErrors('selected_rows');

        $this->actingAs($staff)
            ->from(route('staff.source-record-package-imports.preview', $batch))
            ->post(route('staff.source-record-package-imports.commit', $batch), [
                'selected_rows' => [2, 2],
            ])
            ->assertSessionHasErrors('selected_rows.1');

        $this->assertSame('previewed', $batch->fresh()->status);
    }

    public function test_web_mutation_rejects_a_missing_csrf_token(): void
    {
        $originalEnvironment = app()->environment();

        try {
            // Laravel intentionally bypasses CSRF while running unit tests.
            // Temporarily use a non-testing environment so this request exercises
            // the real web middleware contract instead of the testing bypass.
            app()['env'] = 'production';

            $this->post(route('register'), [
                'name' => 'CSRF Probe',
                'username' => 'csrf.probe',
                'password' => 'SecurePass321!',
                'password_confirmation' => 'SecurePass321!',
            ])->assertStatus(419);
        } finally {
            app()['env'] = $originalEnvironment;
        }

        $this->assertDatabaseMissing('users', [
            'username' => 'csrf.probe',
        ]);
    }
}
