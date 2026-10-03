<?php

namespace Tests\Feature;

use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecordEditingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_smallest_active_holding_blocks_archiving_and_clearing_parcel_area(): void
    {
        [, , $parcel] = $this->records();
        foreach (['status' => 'inactive', 'area_hectares' => null] as $field => $value) {
            try {
                $parcel->fresh()->update($field === 'area_hectares'
                    ? ['area_hectares' => null, 'area_square_meters' => null]
                    : [$field => $value]);
                $this->fail('The smallest active allocation must remain protected.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey($field, $e->errors());
            }
        }
        $this->assertSame('active', $parcel->fresh()->status);
        $this->assertSame('1.0000', $parcel->fresh()->area_hectares);
    }

    public function test_landholding_dates_reject_future_and_reversed_chronology_on_create_and_update(): void
    {
        [$staff, $owner, $parcel, $holding] = $this->records();
        foreach ([
            ['date_acquired' => now()->addDay()->toDateString()],
            ['date_transferred' => now()->addDay()->toDateString()],
            ['date_acquired' => '2025-02-02', 'date_transferred' => '2025-02-01'],
        ] as $dates) {
            $payload = array_merge($this->holdingPayload($parcel, $holding), $dates);
            $field = isset($dates['date_transferred']) ? 'date_transferred' : 'date_acquired';
            $this->actingAs($staff)->post(route('staff.records.landowners.landholdings.store', $owner), $payload)
                ->assertSessionHasErrors($field);
            $this->actingAs($staff)->patch(route('staff.records.landowners.landholdings.update', [$owner, $holding]), $payload)
                ->assertSessionHasErrors($field);
        }
        $this->assertNull($holding->fresh()->date_acquired);
        $this->assertNull($holding->fresh()->date_transferred);
    }

    public function test_optional_historical_dates_remain_supported(): void
    {
        [$staff, $owner, $parcel, $holding] = $this->records();
        $payload = $this->holdingPayload($parcel, $holding);
        $payload['date_transferred'] = '2025-01-01';
        $this->actingAs($staff)->patch(route('staff.records.landowners.landholdings.update', [$owner, $holding]), $payload)
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('2025-01-01', $holding->fresh()->date_transferred->toDateString());
    }

    public function test_stale_reference_forms_cannot_overwrite_newer_values_for_all_three_records(): void
    {
        [$staff, $owner, $parcel, $holding] = $this->records();
        $ownerPayload = ['first_name' => 'Old', 'last_name' => 'Owner', 'expected_record_revision' => $owner->record_revision];
        $parcelPayload = $this->parcelPayload($parcel);
        $holdingPayload = $this->holdingPayload($parcel, $holding);
        $owner->update(['first_name' => 'New']);
        $parcel->update(['remarks' => 'New parcel reference']);
        $holding->update(['remarks' => 'New holding reference']);

        foreach ([
            [route('staff.records.landowners.update', $owner), $ownerPayload],
            [route('staff.records.parcels.update', $parcel), $parcelPayload],
            [route('staff.records.landowners.landholdings.update', [$owner, $holding]), $holdingPayload],
        ] as [$url, $payload]) {
            $this->actingAs($staff)->patch($url, $payload)->assertSessionHasErrors('expected_record_revision');
        }
        $this->assertSame('New', $owner->fresh()->first_name);
        $this->assertSame('New parcel reference', $parcel->fresh()->remarks);
        $this->assertSame('New holding reference', $holding->fresh()->remarks);

        $ownerPayload['expected_record_revision'] = $owner->fresh()->record_revision;
        $this->patch(route('staff.records.landowners.update', $owner), $ownerPayload)->assertSessionHasNoErrors();
        $this->patch(route('staff.records.parcels.update', $parcel), $this->parcelPayload($parcel->fresh()))->assertSessionHasNoErrors();
        $this->patch(route('staff.records.landowners.landholdings.update', [$owner, $holding]), $this->holdingPayload($parcel, $holding->fresh()))->assertSessionHasNoErrors();
    }

    public function test_missing_revision_is_rejected_instead_of_bypassing_stale_edit_protection(): void
    {
        [$staff, $owner, $parcel, $holding] = $this->records();
        foreach ([
            [route('staff.records.landowners.update', $owner), ['first_name' => 'Old', 'last_name' => 'Owner']],
            [route('staff.records.parcels.update', $parcel), $this->parcelPayload($parcel)],
            [route('staff.records.landowners.landholdings.update', [$owner, $holding]), $this->holdingPayload($parcel, $holding)],
        ] as [$url, $payload]) {
            unset($payload['expected_record_revision']);
            $this->actingAs($staff)->patch($url, $payload)->assertSessionHasErrors('expected_record_revision');
        }
    }

    public function test_postgresql_revisions_cover_raw_aba_changes_and_ignore_timestamp_only_writes(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Database revisions target production PostgreSQL.');
        }
        [, $owner, $parcel, $holding] = $this->records();
        foreach ([$owner, $parcel, $holding] as $record) {
            $field = $record instanceof Landowner ? 'first_name' : 'remarks';
            $original = $record->getRawOriginal($field);
            $revision = $record->fresh()->record_revision;
            $query = DB::table($record->getTable())->where('id', $record->id);
            $query->update([$field => 'Changed']);
            $query->update([$field => $original]);
            $this->assertSame($revision + 2, $record->fresh()->record_revision);
            $query->update(['updated_at' => now()->addMinute(), 'record_revision' => 1]);
            $this->assertSame($revision + 2, $record->fresh()->record_revision);
        }
    }

    public function test_rejected_stale_photo_replacement_preserves_current_photo_and_cleans_upload(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [$staff, $owner, $parcel, $holding] = $this->records();
        $payload = $this->holdingPayload($parcel, $holding);
        $path = 'reference-photos/landholdings/current.png';
        Storage::disk('local')->put($path, 'Current photo');
        $holding->update(['reference_photo_path' => $path]);
        $payload['reference_photo'] = UploadedFile::fake()->createWithContent('replacement.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $this->actingAs($staff)->patch(route('staff.records.landowners.landholdings.update', [$owner, $holding]), $payload)
            ->assertSessionHasErrors('expected_record_revision');
        $this->assertSame($path, $holding->fresh()->reference_photo_path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame([$path], Storage::disk('local')->allFiles('reference-photos/landholdings'));
    }

    private function records(): array
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true]);
        $owner = Landowner::create(['first_name' => 'Original', 'last_name' => 'Owner']);
        $parcel = Parcel::create(['parcel_code' => 'EDIT-' . $owner->id, 'area_hectares' => 1, 'status' => 'active']);
        $holding = Landholding::create(['landowner_id' => $owner->id, 'parcel_id' => $parcel->id, 'area_hectares' => 0.0001, 'status' => 'active']);
        return [$staff, $owner->fresh(), $parcel->fresh(), $holding->fresh()];
    }

    private function parcelPayload(Parcel $parcel): array
    {
        return ['parcel_code' => $parcel->parcel_code, 'province' => 'Negros Oriental', 'status' => 'active',
            'area_hectares' => 1, 'geometry_version' => $parcel->geometry_version,
            'expected_record_revision' => $parcel->record_revision];
    }

    private function holdingPayload(Parcel $parcel, Landholding $holding): array
    {
        return ['parcel_id' => $parcel->id, 'area_hectares' => 0.0001, 'status' => 'active',
            'expected_record_revision' => $holding->record_revision];
    }
}
