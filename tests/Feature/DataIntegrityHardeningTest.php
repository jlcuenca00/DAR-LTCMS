<?php

namespace Tests\Feature;

use App\Models\ApplicationParcel;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\LegacyRecord;
use App\Models\Parcel;
use App\Models\SourceRecordPackageImportBatch;
use App\Models\User;
use App\Services\DataIntegrityScanner;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DataIntegrityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_parcel_hectares_are_canonical_and_square_meters_are_derived(): void
    {
        $parcel = $this->parcel('AREA-CANONICAL', 1.0000, 50000);

        $this->assertSame('1.0000', $parcel->fresh()->area_hectares);
        $this->assertSame('10000.00', $parcel->fresh()->area_square_meters);
    }

    public function test_application_transfer_area_cannot_exceed_linked_parcel_area(): void
    {
        $staff = $this->staff();
        $application = $this->application($staff, 'AREA-CEILING');
        $parcel = $this->parcel('AREA-CEILING-PARCEL', 2.0000);

        $this->expectException(ValidationException::class);

        ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'area_hectares' => 8.0000,
        ]);
    }

    public function test_explicit_transferee_shares_must_cover_the_full_transferred_area(): void
    {
        $staff = $this->staff();
        [$application, $transferees] = $this->applicationWithTwoTransferees($staff, 'SHARE-TOTAL');
        $parcel = $this->parcel('SHARE-TOTAL-PARCEL', 4.0000);
        $applicationParcel = ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'area_hectares' => 4.0000,
        ]);

        try {
            $application->transferees = [
                $this->partyRow($transferees[0], [$applicationParcel->id => 0.5000]),
                $this->partyRow($transferees[1], [$applicationParcel->id => 0.5000]),
            ];
            $application->save();
            $this->fail('Invalid explicit transferee shares were accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('transferees', $exception->errors());
        }

        $application->refresh();
        $application->transferees = [
            $this->partyRow($transferees[0], [$applicationParcel->id => 2.0000]),
            $this->partyRow($transferees[1], [$applicationParcel->id => 2.0000]),
        ];
        $application->save();

        $this->assertSame(2.0, (float) data_get($application->fresh()->transferees, '0.parcel_shares.'.$applicationParcel->id));
    }

    public function test_active_landholdings_cannot_overallocate_a_parcel(): void
    {
        $parcel = $this->parcel('CAPACITY-PARCEL', 5.0000);
        $firstOwner = $this->landowner('Capacity', 'One');
        $secondOwner = $this->landowner('Capacity', 'Two');

        Landholding::create([
            'landowner_id' => $firstOwner->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 4.0000,
            'status' => Landholding::STATUS_ACTIVE,
        ]);

        $this->expectException(ValidationException::class);

        Landholding::create([
            'landowner_id' => $secondOwner->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 2.0000,
            'status' => Landholding::STATUS_ACTIVE,
        ]);
    }

    public function test_landowner_record_rejects_non_landowner_user_link(): void
    {
        $staff = $this->staff();

        $this->expectException(ValidationException::class);

        Landowner::create([
            'first_name' => 'Invalid',
            'last_name' => 'Account Link',
            'province' => 'Negros Oriental',
            'user_id' => $staff->id,
        ]);
    }

    public function test_location_values_are_canonicalized_and_invalid_pairings_are_rejected(): void
    {
        $landowner = Landowner::create([
            'first_name' => 'Location',
            'last_name' => 'Canonical',
            'municipality' => 'dumaguete city',
            'barangay' => 'bantayan',
            'province' => 'negros oriental',
        ]);

        $this->assertSame('Dumaguete City', $landowner->municipality);
        $this->assertSame('Bantayan', $landowner->barangay);
        $this->assertSame('Negros Oriental', $landowner->province);

        $this->expectException(ValidationException::class);

        Landowner::create([
            'first_name' => 'Location',
            'last_name' => 'Invalid',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bio-os',
            'province' => 'Negros Oriental',
        ]);
    }

    public function test_application_parcel_pair_is_unique_at_database_level(): void
    {
        $staff = $this->staff();
        $application = $this->application($staff, 'PAIR-UNIQUE');
        $parcel = $this->parcel('PAIR-UNIQUE-PARCEL', 2.0000);

        ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'area_hectares' => 1.0000,
        ]);

        $this->expectException(QueryException::class);

        ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'area_hectares' => 1.0000,
        ]);
    }

    public function test_landholding_foreign_keys_restrict_destructive_parent_deletes(): void
    {
        $parcel = $this->parcel('RESTRICT-DELETE', 2.0000);
        $landowner = $this->landowner('Restrict', 'Delete');

        Landholding::create([
            'landowner_id' => $landowner->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 1.0000,
            'status' => Landholding::STATUS_ACTIVE,
        ]);

        $this->expectException(QueryException::class);
        $parcel->delete();
    }

    public function test_source_import_preview_marks_same_file_duplicate_references(): void
    {
        $staff = $this->staff();
        $row = fn (int $index) => [
            'row_index' => $index,
            'status' => 'valid',
            'possible_duplicate' => false,
            'errors' => [],
            'warnings' => [],
            'data' => [
                'include_title' => true,
                'include_landholding' => false,
                'include_parcel_source' => false,
                'include_historical_clearance' => false,
                'title_number' => 'TCT-BATCH-DUPLICATE',
            ],
        ];

        $batch = SourceRecordPackageImportBatch::create([
            'original_filename' => 'duplicate.csv',
            'status' => 'previewed',
            'total_rows' => 2,
            'valid_rows' => 2,
            'error_rows' => 0,
            'duplicate_rows' => 0,
            'uploaded_by_user_id' => $staff->id,
            'preview_rows' => [$row(2), $row(3)],
            'summary' => [],
        ]);

        $batch->refresh();
        $this->assertSame(0, $batch->valid_rows);
        $this->assertSame(2, $batch->error_rows);
        $this->assertSame(2, $batch->duplicate_rows);
        $this->assertSame('error', $batch->preview_rows[0]['status']);
        $this->assertTrue($batch->preview_rows[1]['possible_duplicate']);
    }

    public function test_source_reference_is_revalidated_immediately_before_save(): void
    {
        $this->sourceTitle('TCT-COMMIT-RECHECK');

        $this->expectException(ValidationException::class);
        $this->sourceTitle('tct-commit-recheck');
    }

    public function test_release_gate_rejects_malformed_historical_share_rows(): void
    {
        $staff = $this->staff();
        [$application, $transferees] = $this->applicationWithTwoTransferees(
            $staff,
            'RELEASE-SHARE-GATE',
            LandTransferApplication::STATUS_ENDORSED_PARPO
        );
        $parcel = $this->parcel('RELEASE-SHARE-GATE-PARCEL', 4.0000);
        $applicationParcel = ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'area_hectares' => 4.0000,
        ]);

        LandTransferApplication::withoutEvents(function () use ($application, $transferees, $applicationParcel) {
            $application->transferees = [
                $this->partyRow($transferees[0], [$applicationParcel->id => 0.5000]),
                $this->partyRow($transferees[1], [$applicationParcel->id => 0.5000]),
            ];
            $application->save();
        });

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application))
            ->assertSessionHasErrors(['validation', 'transferee_shares']);

        $this->assertSame(
            LandTransferApplication::STATUS_ENDORSED_PARPO,
            $application->fresh()->status
        );
    }

    public function test_read_only_scanner_reports_preexisting_area_corruption(): void
    {
        $parcel = $this->parcel('SCANNER-MISMATCH', 1.0000);

        DB::table('parcels')->where('id', $parcel->id)->update([
            'area_square_meters' => 50000,
        ]);

        $result = app(DataIntegrityScanner::class)->scan();
        $codes = collect($result['issues'])->pluck('code');

        $this->assertFalse($result['clean']);
        $this->assertTrue($codes->contains('parcel_area_mismatch'));
        $this->assertSame(50000.0, (float) DB::table('parcels')->where('id', $parcel->id)->value('area_square_meters'));
    }

    private function staff(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);
    }

    private function landowner(string $firstName, string $lastName): Landowner
    {
        return Landowner::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'province' => 'Negros Oriental',
        ]);
    }

    private function parcel(string $code, float $hectares, ?float $squareMeters = null): Parcel
    {
        return Parcel::create([
            'parcel_code' => $code,
            'title_no' => 'T-'.$code,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'area_hectares' => $hectares,
            'area_square_meters' => $squareMeters ?? ($hectares * 10000),
            'status' => 'active',
        ]);
    }

    private function application(User $staff, string $code, string $status = LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW): LandTransferApplication
    {
        $transferor = $this->landowner('Transferor', $code);
        $transferee = $this->landowner('Transferee', $code);

        return LandTransferApplication::create([
            'application_code' => $code,
            'transferor_name' => $transferor->full_name,
            'transferors' => [$this->partyRow($transferor)],
            'transferee_name' => $transferee->full_name,
            'transferees' => [$this->partyRow($transferee)],
            'transferor_landowner_id' => $transferor->id,
            'transferee_landowner_id' => $transferee->id,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => $status,
            'encoded_by' => $staff->id,
        ]);
    }

    private function applicationWithTwoTransferees(User $staff, string $code, string $status = LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW): array
    {
        $transferor = $this->landowner('Transferor', $code);
        $first = $this->landowner('TransfereeA', $code);
        $second = $this->landowner('TransfereeB', $code);

        $application = LandTransferApplication::create([
            'application_code' => $code,
            'transferor_name' => $transferor->full_name,
            'transferors' => [$this->partyRow($transferor)],
            'transferee_name' => $first->full_name.'; '.$second->full_name,
            'transferees' => [$this->partyRow($first), $this->partyRow($second)],
            'transferor_landowner_id' => $transferor->id,
            'transferee_landowner_id' => $first->id,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => $status,
            'encoded_by' => $staff->id,
        ]);

        return [$application, [$first, $second]];
    }

    private function partyRow(Landowner $landowner, array $shares = []): array
    {
        return [
            'name' => $landowner->full_name,
            'landowner_id' => $landowner->id,
            'parcel_shares' => $shares,
        ];
    }

    private function sourceTitle(string $titleNumber): LegacyRecord
    {
        return LegacyRecord::create([
            'record_type' => LegacyRecord::TYPE_TITLE,
            'origin' => LegacyRecord::ORIGIN_IMPORTED,
            'source_record_scope' => LegacyRecord::SOURCE_SCOPE_REFERENCE_ONLY,
            'title_number' => $titleNumber,
            'landowner_name' => 'Source Reference Owner',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'source_book' => 'Integrity Test Book',
            'transcribed_by' => 'Integrity Test',
            'transcription_date' => now()->toDateString(),
        ]);
    }
}
