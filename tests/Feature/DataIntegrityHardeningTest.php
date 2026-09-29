<?php

namespace Tests\Feature;

use App\Models\ApplicationParcel;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\LegacyRecord;
use App\Models\Parcel;
use App\Models\SourceRecordPackage;
use App\Models\SourceRecordPackageImportBatch;
use App\Models\User;
use App\Services\DataIntegrityScanner;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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

    public function test_official_negros_oriental_location_coverage_is_not_truncated(): void
    {
        $municipalities = config('dar_locations.municipalities');

        $this->assertCount(25, $municipalities);
        $this->assertSame(557, collect($municipalities)->sum(fn (array $barangays) => count($barangays)));
        $this->assertArrayHasKey('Vallehermoso', $municipalities);
        $this->assertContains('Bantayan', $municipalities['Dumaguete City']);
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

    public function test_testing_environment_rejects_silently_discarded_mass_assignment_attributes(): void
    {
        $staff = $this->staff();

        $this->expectException(MassAssignmentException::class);

        LandTransferApplication::create([
            'application_code' => 'STRICT-MASS-ASSIGNMENT-001',
            'transferor_name' => 'Strict Transferor',
            'transferee_name' => 'Strict Transferee',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
            'attribute_that_must_not_be_silently_discarded' => 'unexpected',
        ]);
    }

    public function test_source_package_link_to_existing_parcel_rolls_back_when_record_reference_would_duplicate(): void
    {
        $staff = $this->staff();
        $parcel = $this->parcel('PKG-DUP-LINK-001', 1.0000);

        LegacyRecord::create([
            'record_type' => LegacyRecord::TYPE_PARCEL_SOURCE,
            'origin' => LegacyRecord::ORIGIN_IMPORTED,
            'source_record_scope' => LegacyRecord::SOURCE_SCOPE_REFERENCE_ONLY,
            'parcel_code' => $parcel->parcel_code,
            'source_book' => 'Existing Parcel Source Book',
            'transcribed_by' => 'Integrity Test',
            'transcription_date' => now()->toDateString(),
        ]);

        $package = SourceRecordPackage::create([
            'package_code' => 'PKG-DUP-LINK',
            'status' => SourceRecordPackage::STATUS_ENCODED,
            'source_record_scope' => LegacyRecord::SOURCE_SCOPE_REFERENCE_ONLY,
            'encoded_by_user_id' => $staff->id,
            'parcel_code' => 'PKG-TEMP-LINK-001',
            'source_book' => 'Package Link Test Book',
            'transcribed_by' => 'Integrity Test',
            'transcription_date' => now()->toDateString(),
        ]);

        $record = LegacyRecord::create([
            'record_type' => LegacyRecord::TYPE_PARCEL_SOURCE,
            'origin' => LegacyRecord::ORIGIN_ENCODED,
            'source_record_scope' => LegacyRecord::SOURCE_SCOPE_REFERENCE_ONLY,
            'source_record_package_id' => $package->id,
            'encoded_by_user_id' => $staff->id,
            'parcel_code' => 'PKG-TEMP-LINK-001',
            'source_book' => 'Package Link Test Book',
            'transcribed_by' => 'Integrity Test',
            'transcription_date' => now()->toDateString(),
        ]);

        $this->actingAs($staff)
            ->post(route('staff.source-record-packages.link-parcel', $package), [
                'parcel_id' => $parcel->id,
            ])
            ->assertSessionHasErrors('parcel_code');

        $package->refresh();
        $record->refresh();

        $this->assertNull($package->parcel_id);
        $this->assertSame(SourceRecordPackage::STATUS_ENCODED, $package->status);
        $this->assertSame('PKG-TEMP-LINK-001', $package->parcel_code);
        $this->assertNull($record->parcel_id);
        $this->assertSame('PKG-TEMP-LINK-001', $record->parcel_code);
    }

    public function test_source_package_create_parcel_rolls_back_when_record_reference_would_duplicate(): void
    {
        $staff = $this->staff();
        $newParcelCode = 'PKG-DUP-CREATE-001';

        LegacyRecord::create([
            'record_type' => LegacyRecord::TYPE_PARCEL_SOURCE,
            'origin' => LegacyRecord::ORIGIN_IMPORTED,
            'source_record_scope' => LegacyRecord::SOURCE_SCOPE_REFERENCE_ONLY,
            'parcel_code' => $newParcelCode,
            'source_book' => 'Existing Parcel Source Book',
            'transcribed_by' => 'Integrity Test',
            'transcription_date' => now()->toDateString(),
        ]);

        $package = SourceRecordPackage::create([
            'package_code' => 'PKG-DUP-CREATE',
            'status' => SourceRecordPackage::STATUS_ENCODED,
            'source_record_scope' => LegacyRecord::SOURCE_SCOPE_REFERENCE_ONLY,
            'encoded_by_user_id' => $staff->id,
            'parcel_code' => 'PKG-TEMP-CREATE-001',
            'source_book' => 'Package Create Test Book',
            'transcribed_by' => 'Integrity Test',
            'transcription_date' => now()->toDateString(),
        ]);

        $record = LegacyRecord::create([
            'record_type' => LegacyRecord::TYPE_PARCEL_SOURCE,
            'origin' => LegacyRecord::ORIGIN_ENCODED,
            'source_record_scope' => LegacyRecord::SOURCE_SCOPE_REFERENCE_ONLY,
            'source_record_package_id' => $package->id,
            'encoded_by_user_id' => $staff->id,
            'parcel_code' => 'PKG-TEMP-CREATE-001',
            'source_book' => 'Package Create Test Book',
            'transcribed_by' => 'Integrity Test',
            'transcription_date' => now()->toDateString(),
        ]);

        $this->actingAs($staff)
            ->post(route('staff.source-record-packages.create-parcel', $package), [
                'parcel_code' => $newParcelCode,
                'title_no' => null,
                'municipality' => 'Dumaguete City',
                'barangay' => 'Bantayan',
                'province' => 'Negros Oriental',
                'area_hectares' => 1.0000,
                'geometry_geojson' => null,
                'status' => 'active',
                'remarks' => null,
                'landowner_id' => null,
                'date_acquired' => null,
            ])
            ->assertSessionHasErrors('parcel_code');

        $package->refresh();
        $record->refresh();

        $this->assertDatabaseMissing('parcels', ['parcel_code' => $newParcelCode]);
        $this->assertNull($package->parcel_id);
        $this->assertSame(SourceRecordPackage::STATUS_ENCODED, $package->status);
        $this->assertSame('PKG-TEMP-CREATE-001', $package->parcel_code);
        $this->assertNull($record->parcel_id);
        $this->assertSame('PKG-TEMP-CREATE-001', $record->parcel_code);
    }

    public function test_parcel_area_cannot_be_reduced_below_active_landholding_allocation(): void
    {
        $parcel = $this->parcel('PARCEL-AREA-GUARD-001', 2.0000);
        $landowner = $this->landowner('Area', 'Guard');

        Landholding::create([
            'landowner_id' => $landowner->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 2.0000,
            'status' => Landholding::STATUS_ACTIVE,
        ]);

        try {
            $parcel->update(['area_hectares' => 1.5000]);
            $this->fail('Expected Parcel area reduction to be rejected.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('area_hectares', $exception->errors());
        }

        $this->assertSame(2.0, (float) $parcel->fresh()->area_hectares);
    }

    public function test_parcel_cannot_be_archived_while_active_landholdings_exist(): void
    {
        $parcel = $this->parcel('PARCEL-ARCHIVE-GUARD-001', 2.0000);
        $landowner = $this->landowner('Archive', 'Guard');

        Landholding::create([
            'landowner_id' => $landowner->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 2.0000,
            'status' => Landholding::STATUS_ACTIVE,
        ]);

        try {
            $parcel->update(['status' => 'inactive']);
            $this->fail('Expected Parcel archival to be rejected.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame('active', $parcel->fresh()->status);
    }

    public function test_active_landholding_cannot_be_created_for_inactive_parcel(): void
    {
        $parcel = $this->parcel('PARCEL-INACTIVE-LINK-GUARD-001', 2.0000);
        $parcel->update(['status' => 'inactive']);

        $landowner = $this->landowner('Inactive', 'Link');

        try {
            Landholding::create([
                'landowner_id' => $landowner->id,
                'parcel_id' => $parcel->id,
                'area_hectares' => 1.0000,
                'status' => Landholding::STATUS_ACTIVE,
            ]);
            $this->fail('Expected active Landholding creation on an inactive Parcel to be rejected.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('parcel_id', $exception->errors());
        }

        $this->assertDatabaseMissing('landholdings', [
            'landowner_id' => $landowner->id,
            'parcel_id' => $parcel->id,
            'status' => Landholding::STATUS_ACTIVE,
        ]);
    }

    public function test_integrity_scanner_reports_inactive_parcel_with_active_landholding(): void
    {
        $parcel = $this->parcel('PARCEL-INACTIVE-HOLDING-001', 2.0000);
        $landowner = $this->landowner('Scanner', 'Guard');

        Landholding::create([
            'landowner_id' => $landowner->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 2.0000,
            'status' => Landholding::STATUS_ACTIVE,
        ]);

        DB::table('parcels')
            ->where('id', $parcel->id)
            ->update(['status' => 'inactive']);

        $result = app(\App\Services\DataIntegrityScanner::class)->scan();
        $codes = collect($result['issues'])->pluck('code');

        $this->assertFalse($result['clean']);
        $this->assertTrue($codes->contains('inactive_parcel_active_landholding'));
    }

    public function test_application_party_rows_reject_duplicate_landowner_links(): void
    {
        $staff = $this->staff();
        $owner = $this->landowner('Duplicate', 'Party');
        $transferee = $this->landowner('Unique', 'Transferee');

        $this->expectException(ValidationException::class);

        LandTransferApplication::create([
            'application_code' => 'PARTY-DUPLICATE-001',
            'transferor_name' => 'Will be normalized',
            'transferors' => [
                $this->partyRow($owner),
                $this->partyRow($owner),
            ],
            'transferee_name' => $transferee->full_name,
            'transferees' => [$this->partyRow($transferee)],
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);
    }

    public function test_application_party_json_synchronizes_compatibility_columns(): void
    {
        $staff = $this->staff();
        $transferor = $this->landowner('Canonical', 'Transferor');
        $transferee = $this->landowner('Canonical', 'Transferee');
        $wrongOwner = $this->landowner('Wrong', 'Compatibility');

        $application = LandTransferApplication::create([
            'application_code' => 'PARTY-CANONICAL-001',
            'transferor_name' => 'Wrong Transferor Summary',
            'transferors' => [$this->partyRow($transferor)],
            'transferee_name' => 'Wrong Transferee Summary',
            'transferees' => [$this->partyRow($transferee)],
            'transferor_landowner_id' => $wrongOwner->id,
            'transferee_landowner_id' => $wrongOwner->id,
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        $this->assertSame($transferor->full_name, $application->transferor_name);
        $this->assertSame($transferee->full_name, $application->transferee_name);
        $this->assertSame($transferor->id, (int) $application->transferor_landowner_id);
        $this->assertSame($transferee->id, (int) $application->transferee_landowner_id);
    }

    public function test_open_application_parcel_requires_positive_transfer_area(): void
    {
        $staff = $this->staff();
        $application = $this->application($staff, 'APP-PARCEL-POSITIVE-001');
        $parcel = $this->parcel('APP-PARCEL-POSITIVE-MASTER', 2.0000);

        try {
            ApplicationParcel::create([
                'land_transfer_application_id' => $application->id,
                'parcel_id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code,
                'area_hectares' => null,
            ]);
            $this->fail('Expected an open application Parcel without a positive transfer area to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('parcel_id', $exception->errors());
        }

        $this->assertDatabaseMissing('application_parcels', [
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
        ]);
    }

    public function test_open_application_parcel_cannot_link_inactive_master_parcel(): void
    {
        $staff = $this->staff();
        $application = $this->application($staff, 'APP-PARCEL-ACTIVE-001');
        $parcel = $this->parcel('APP-PARCEL-INACTIVE-MASTER', 2.0000);
        $parcel->update(['status' => 'inactive']);

        try {
            ApplicationParcel::create([
                'land_transfer_application_id' => $application->id,
                'parcel_id' => $parcel->id,
                'parcel_code' => $parcel->parcel_code,
                'area_hectares' => 1.0000,
            ]);
            $this->fail('Expected an inactive master Parcel link to be rejected for an open application.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('parcel_id', $exception->errors());
        }

        $this->assertDatabaseMissing('application_parcels', [
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
        ]);
    }

    public function test_integrity_scanner_reports_preexisting_application_party_drift_and_invalid_current_parcel(): void
    {
        $staff = $this->staff();
        $application = $this->application($staff, 'APP-SCANNER-2C-001');
        $parcel = $this->parcel('APP-SCANNER-2C-PARCEL', 2.0000);

        $applicationParcel = ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'area_hectares' => 1.0000,
        ]);

        LandTransferApplication::withoutEvents(function () use ($application) {
            $application->transferor_name = 'Drifted summary';
            $application->save();
        });

        DB::table('application_parcels')
            ->where('id', $applicationParcel->id)
            ->update(['area_hectares' => null]);

        $result = app(DataIntegrityScanner::class)->scan();
        $codes = collect($result['issues'])->pluck('code');

        $this->assertFalse($result['clean']);
        $this->assertTrue($codes->contains('invalid_application_party_links'));
        $this->assertTrue($codes->contains('invalid_current_application_parcels'));
    }

    public function test_integrity_scanner_artisan_command_boots_and_reports_read_only_mode(): void
    {
        $exitCode = Artisan::call('dar:scan-data-integrity', ['--json' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('"read_only": true', $output);
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
