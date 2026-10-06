<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LegacyRecord;
use App\Models\SourceRecordPackage;
use App\Models\SourceRecordPackageImportBatch;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class SourceRecordPackageImportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_preview_and_selected_commit_preserve_all_source_sections_without_creating_master_records(): void
    {
        [$staff, $geodetic] = $this->users();
        $rows = [$this->row('ONE'), $this->row('TWO', LegacyRecord::SOURCE_SCOPE_HISTORICAL), $this->row('UNSELECTED')];
        $batch = $this->preview($staff, $rows);

        $this->assertSame(3, $batch->total_rows);
        $this->assertSame(3, $batch->valid_rows);
        $this->assertSame(0, $batch->error_rows);
        $this->assertSame(0, $batch->duplicate_rows);
        $this->assertSame([2, 3, 4], array_column($batch->preview_rows, 'row_index'));
        $this->assertDatabaseCount('source_record_packages', 0);
        $this->assertDatabaseCount('legacy_records', 0);
        $masters = $this->masterSnapshot();

        $this->get(route('staff.source-record-package-imports.preview', $batch))->assertOk()->assertViewHas('batch');
        $this->commit($batch)->assertRedirect(route('staff.legacy-records.index'))->assertSessionHas('success');

        $batch->refresh();
        $this->assertSame('committed', $batch->status);
        $this->assertSame(2, $batch->committed_rows);
        $this->assertSame($staff->id, $batch->committed_by_user_id);
        $this->assertNotNull($batch->committed_at);
        $this->assertDatabaseCount('source_record_packages', 2);
        $this->assertDatabaseCount('legacy_records', 8);
        $this->assertDatabaseMissing('source_record_packages', ['title_number' => 'TCT-UNSELECTED']);

        foreach (array_slice($rows, 0, 2) as $row) {
            $package = SourceRecordPackage::where('title_number', $row['title_number'])->firstOrFail();
            $this->assertSame(SourceRecordPackage::STATUS_ENCODED, $package->status);
            $this->assertSame($staff->id, $package->encoded_by_user_id);
            $this->assertNull($package->parcel_id);
            $this->assertNull($package->landowner_id);
            $this->assertSame($row['source_record_scope'], $package->source_record_scope);
            $this->assertSame($this->geometry(), $package->source_geometry_geojson);
            $this->assertSame($row['boundary_description'], $package->boundary_description);
            $records = $package->records()->get()->keyBy('record_type');
            $this->assertEqualsCanonicalizing(array_keys(LegacyRecord::RECORD_TYPES), $records->keys()->all());

            foreach ($records as $record) {
                $this->assertSame(LegacyRecord::ORIGIN_IMPORTED, $record->origin);
                $this->assertSame($staff->id, $record->encoded_by_user_id);
                $this->assertSame($row['source_record_scope'], $record->source_record_scope);
                $this->assertNull($record->parcel_id);
                $this->assertNull($record->landowner_id);
                foreach (['parcel_code', 'title_number', 'landowner_name', 'lot_number', 'survey_number', 'area_hectares', 'crop_or_land_use', 'province', 'source_book', 'page_number', 'transcribed_by', 'remarks', 'source_notes'] as $field) {
                    $this->assertSame($row[$field], $package->{$field}, 'Package '.$field);
                    $this->assertSame($row[$field], $record->{$field}, 'Source record '.$field);
                }
                $this->assertSame('Dumaguete City', $record->municipality);
                $this->assertSame('Bantayan', $record->barangay);
                $this->assertSame($row['transcription_date'], $record->transcription_date->toDateString());
                $isParcelSource = $record->record_type === LegacyRecord::TYPE_PARCEL_SOURCE;
                $isClearance = $record->record_type === LegacyRecord::TYPE_HISTORICAL_CLEARANCE;
                $this->assertSame($isParcelSource ? $this->geometry() : null, $record->source_geometry_geojson);
                $this->assertSame($isParcelSource ? $row['boundary_description'] : null, $record->boundary_description);
                $this->assertSame($isClearance ? $row['control_number'] : null, $record->control_number);
                $this->assertSame($isClearance ? $row['transferor_name'] : null, $record->transferor_name);
                $this->assertSame($isClearance ? $row['transferee_name'] : null, $record->transferee_name);
                $this->assertSame($record->record_type === LegacyRecord::TYPE_LANDHOLDING ? $row['landholding_reference_number'] : null, $record->landholding_reference_number);
            }
        }
        $this->assertSame($masters, $this->masterSnapshot(), 'Importing source evidence must not create master parcels, owners, holdings or live clearances.');
        $this->assertSame(1, AuditLog::where('action', 'source_record_package_import_committed')->count());
        $this->assertDatabaseCount('system_notifications', 1);
        $notification = SystemNotification::firstOrFail();
        $this->assertSame($geodetic->id, $notification->user_id);
        $this->assertSame('geodetic_reference_imported', $notification->type);
        $this->assertSame(2, $notification->data['committed_rows']);
    }

    public function test_repeating_a_committed_batch_does_not_duplicate_records_audit_or_notifications(): void
    {
        [$staff] = $this->users();
        $batch = $this->preview($staff, [$this->row('ONE'), $this->row('TWO')]);
        $this->commit($batch)->assertSessionHas('success');
        $before = $this->snapshot();

        $this->commit($batch->fresh())->assertRedirect()->assertSessionHas('success', 'This import batch has already been committed.');

        $this->assertSame($before, $this->snapshot());
    }

    public static function failureCases(): array
    {
        return ['second package child' => ['record'], 'commit audit' => ['audit'], 'commit notification' => ['notification']];
    }

    #[DataProvider('failureCases')]
    public function test_commit_failure_rolls_back_packages_children_batch_audit_and_notifications(string $failure): void
    {
        [$staff] = $this->users();
        $batch = $this->preview($staff, [$this->row('ONE'), $this->row('TWO')]);
        $before = $this->snapshot();
        $model = match ($failure) {
            'record' => LegacyRecord::class,
            'audit' => AuditLog::class,
            'notification' => SystemNotification::class,
        };
        $event = 'eloquent.created: '.$model;
        $injected = false;
        Event::listen($event, function ($record) use ($failure, &$injected, $batch): void {
            if ($failure === 'record' && LegacyRecord::count() !== 5) {
                return;
            }
            if ($failure === 'audit' && $record->action !== 'source_record_package_import_committed') {
                return;
            }
            if ($failure === 'notification' && $record->type !== 'geodetic_reference_imported') {
                return;
            }
            $injected = true;
            $this->assertSame(2, SourceRecordPackage::count());
            $this->assertSame($failure === 'record' ? 5 : 8, LegacyRecord::count());
            $this->assertSame($failure === 'record' ? 'previewed' : 'committed', $batch->fresh()->status);
            throw new RuntimeException('Forced source import commit failure.');
        });
        $this->withoutExceptionHandling();
        try {
            try {
                $this->commit($batch);
                $this->fail('Expected the injected import failure.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Forced source import commit failure.', $exception->getMessage());
            }
        } finally {
            Event::forget($event);
        }
        $this->assertTrue($injected, 'The request must reach the intended boundary after real writes.');
        $this->assertSame($before, $this->snapshot());

        // The rolled-back preview must remain usable for a clean retry.
        $this->commit($batch->fresh())->assertSessionHas('success');
        $this->assertDatabaseCount('source_record_packages', 2);
        $this->assertDatabaseCount('legacy_records', 8);
        $this->assertDatabaseCount('system_notifications', 1);
        $this->assertSame(1, AuditLog::where('action', 'source_record_package_import_committed')->count());
    }

    public function test_reference_created_after_preview_rejects_commit_and_removes_earlier_imported_rows(): void
    {
        [$staff] = $this->users();
        $batch = $this->preview($staff, [$this->row('ONE'), $this->row('TWO')]);
        LegacyRecord::create([
            'record_type' => LegacyRecord::TYPE_TITLE,
            'origin' => LegacyRecord::ORIGIN_ENCODED,
            'source_record_scope' => LegacyRecord::SOURCE_SCOPE_REFERENCE_ONLY,
            'title_number' => 'tct-two',
            'landowner_name' => 'Existing source owner',
            'source_book' => 'Existing source book',
            'transcribed_by' => 'Existing encoder',
            'transcription_date' => now()->toDateString(),
            'encoded_by_user_id' => $staff->id,
        ]);
        $before = $this->snapshot();

        $this->commit($batch)->assertRedirect()->assertSessionHasErrors('title_number');

        $this->assertSame($before, $this->snapshot());
    }

    private function users(): array
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true]);
        $geodetic = User::factory()->create(['role' => User::ROLE_GEODETIC, 'is_active' => true]);
        User::factory()->create(['role' => User::ROLE_GEODETIC, 'is_active' => false]);
        return [$staff, $geodetic];
    }

    private function preview(User $staff, array $rows): SourceRecordPackageImportBatch
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($stream, array_values($row));
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        $this->actingAs($staff)->post(route('staff.source-record-package-imports.preview.store'), [
            'import_file' => UploadedFile::fake()->createWithContent('source-workflow.csv', $csv),
        ])->assertRedirect()->assertSessionHas('success');
        return SourceRecordPackageImportBatch::latest('id')->firstOrFail();
    }

    private function commit(SourceRecordPackageImportBatch $batch): \Illuminate\Testing\TestResponse
    {
        return $this->from(route('staff.source-record-package-imports.preview', $batch))
            ->post(route('staff.source-record-package-imports.commit', $batch), ['selected_rows' => [2, 3]]);
    }

    private function row(string $suffix, string $scope = LegacyRecord::SOURCE_SCOPE_REFERENCE_ONLY): array
    {
        return [
            'include_title' => 'yes', 'include_landholding' => 'yes', 'include_parcel_source' => 'yes', 'include_historical_clearance' => 'yes',
            'source_record_scope' => $scope, 'landowner_name' => 'CSV Owner '.$suffix,
            'parcel_code' => 'SRC-'.$suffix, 'title_number' => 'TCT-'.$suffix,
            'landholding_reference_number' => 'LH-'.$suffix, 'control_number' => 'CTRL-'.$suffix,
            'transferor_name' => 'Transferor '.$suffix, 'transferee_name' => 'Transferee '.$suffix,
            'lot_number' => 'LOT-'.$suffix, 'survey_number' => 'SUR-'.$suffix,
            'area_hectares' => '1.2500', 'crop_or_land_use' => 'Agricultural',
            'barangay' => 'bantayan', 'municipality' => 'dumaguete city', 'province' => 'Negros Oriental',
            'source_geometry_geojson' => json_encode($this->geometry(), JSON_THROW_ON_ERROR),
            'boundary_description' => 'Boundary '.$suffix, 'source_book' => 'CSV Book '.$suffix,
            'page_number' => '12', 'transcribed_by' => 'CSV Encoder', 'transcription_date' => now()->toDateString(),
            'remarks' => 'Remarks, with comma '.$suffix, 'source_notes' => "Quoted \"source\" notes\nSecond line ".$suffix,
        ];
    }

    private function geometry(): array
    {
        return ['type' => 'Polygon', 'coordinates' => [[[123.308, 9.3064], [123.309, 9.3064], [123.309, 9.3072], [123.308, 9.3072], [123.308, 9.3064]]]];
    }

    private function rows(string $table): array
    {
        return DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }

    private function masterSnapshot(): array
    {
        return array_map(fn ($table) => $this->rows($table), ['parcels', 'landowners', 'landholdings', 'land_transfer_applications', 'application_clearances']);
    }

    private function snapshot(): array
    {
        return [
            'packages' => $this->rows('source_record_packages'), 'records' => $this->rows('legacy_records'),
            'batches' => $this->rows('source_record_package_import_batches'), 'notifications' => $this->rows('system_notifications'),
            'masters' => $this->masterSnapshot(),
            // The request fallback audit may legitimately record a failed attempt.
            'domain_audit' => DB::table('audit_logs')->whereIn('action', ['source_record_package_import_previewed', 'source_record_package_import_committed'])
                ->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
        ];
    }
}
