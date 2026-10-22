<?php

namespace Tests\Feature;

use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\LegacyRecord;
use App\Models\Parcel;
use App\Models\SourceRecordPackage;
use App\Models\User;
use App\Services\LandholdingAreaValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceRecordPackageLandholdingConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_active_conversion_creates_linked_master_record_and_updates_five_hectare_total(): void
    {
        [$staff, $package, $landowner, $parcel] = $this->fixture();

        $this->actingAs($staff)->get(route('staff.source-record-packages.landholding.review', $package))
            ->assertOk()->assertSee('Confirm &amp; Create Landholding', false);

        $this->actingAs($staff)->post(route('staff.source-record-packages.landholding.store', $package), $this->input($package))
            ->assertRedirect(route('staff.source-record-packages.show', $package));

        $holding = Landholding::firstOrFail();
        $this->assertSame($landowner->id, $holding->landowner_id);
        $this->assertSame($parcel->id, $holding->parcel_id);
        $this->assertSame($package->id, $holding->source_record_package_id);
        $this->assertSame('LH-REVIEW-001', $holding->source_reference_number);
        $this->assertSame(Landholding::STATUS_ACTIVE, $holding->status);
        $this->assertSame(2.5, (float) app(LandholdingAreaValidationService::class)
            ->forLandowner($landowner)['current_active_total']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'landholding_created_from_source_package']);
    }

    public function test_historical_source_creates_non_active_record_not_counted_in_hectares(): void
    {
        [$staff, $package, $landowner] = $this->fixture(LegacyRecord::SOURCE_SCOPE_HISTORICAL);
        $this->actingAs($staff)->post(route('staff.source-record-packages.landholding.store', $package), $this->input($package))
            ->assertRedirect();
        $this->assertSame(Landholding::STATUS_HISTORICAL, Landholding::firstOrFail()->status);
        $this->assertSame(0.0, (float) app(LandholdingAreaValidationService::class)
            ->forLandowner($landowner)['current_active_total']);
    }

    public function test_reference_only_package_cannot_create_master_landholding(): void
    {
        [$staff, $package] = $this->fixture(LegacyRecord::SOURCE_SCOPE_REFERENCE_ONLY);
        $this->actingAs($staff)->post(route('staff.source-record-packages.landholding.store', $package), $this->input($package))
            ->assertSessionHasErrors('conversion');
        $this->assertDatabaseCount('landholdings', 0);
    }

    public function test_double_submission_and_existing_pair_are_rejected(): void
    {
        [$staff, $package] = $this->fixture();
        $this->actingAs($staff)->post(route('staff.source-record-packages.landholding.store', $package), $this->input($package))
            ->assertRedirect();
        $this->actingAs($staff)->post(route('staff.source-record-packages.landholding.store', $package), $this->input($package))
            ->assertSessionHasErrors('conversion');
        $this->assertDatabaseCount('landholdings', 1);
    }

    public function test_area_capacity_and_missing_confirmation_are_enforced(): void
    {
        [$staff, $package] = $this->fixture();
        $this->actingAs($staff)->post(route('staff.source-record-packages.landholding.store', $package),
            array_merge($this->input($package), ['verified_source' => '0']))->assertSessionHasErrors('verified_source');
        $this->actingAs($staff)->post(route('staff.source-record-packages.landholding.store', $package),
            array_merge($this->input($package), ['area_hectares' => '6.0000']))->assertSessionHasErrors('area_hectares');
        $this->assertDatabaseCount('landholdings', 0);
    }

    public function test_stale_revision_blocks_conversion(): void
    {
        [$staff, $package] = $this->fixture();
        $this->actingAs($staff)->post(route('staff.source-record-packages.landholding.store', $package),
            array_merge($this->input($package), ['expected_record_revision' => 999]))->assertSessionHasErrors('expected_record_revision');
        $this->assertDatabaseCount('landholdings', 0);
    }

    public function test_conversion_requires_both_links_and_landholding_evidence(): void
    {
        [$staff, $package] = $this->fixture();
        $package->update(['landowner_id' => null]);
        $this->actingAs($staff)->post(route('staff.source-record-packages.landholding.store', $package),
            $this->input($package->fresh()))->assertSessionHasErrors('conversion');
        $this->assertDatabaseCount('landholdings', 0);
    }

    public function test_non_staff_users_cannot_review_or_create_landholding(): void
    {
        [, $package] = $this->fixture();
        foreach ([User::ROLE_LANDOWNER, User::ROLE_GEODETIC] as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->actingAs($user)->get(route('staff.source-record-packages.landholding.review', $package))->assertForbidden();
            $this->actingAs($user)->post(route('staff.source-record-packages.landholding.store', $package),
                $this->input($package))->assertForbidden();
        }
        $this->assertDatabaseCount('landholdings', 0);
    }


    public function test_source_landowner_link_cannot_change_after_conversion(): void
    {
        [$staff, $package] = $this->fixture();
        $this->actingAs($staff)->post(route('staff.source-record-packages.landholding.store', $package), $this->input($package))
            ->assertRedirect();

        $otherOwner = Landowner::create(['first_name' => 'Different', 'last_name' => 'Owner']);
        $this->actingAs($staff)->post(route('staff.source-record-packages.link-landowner', $package), [
            'landowner_id' => $otherOwner->id,
            'expected_record_revision' => $package->fresh()->record_revision,
        ])->assertSessionHasErrors('landowner_id');

        $this->assertNotSame($otherOwner->id, $package->fresh()->landowner_id);
        $this->assertDatabaseCount('landholdings', 1);
    }

    public function test_existing_active_area_and_duplicate_pair_do_not_create_extra_records(): void
    {
        [$staff, $package, $landowner, $parcel] = $this->fixture();
        Landholding::create([
            'landowner_id' => $landowner->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 1.5000,
            'status' => Landholding::STATUS_ACTIVE,
        ]);

        $this->actingAs($staff)->post(route('staff.source-record-packages.landholding.store', $package), $this->input($package))
            ->assertSessionHasErrors('conversion');
        $this->assertDatabaseCount('landholdings', 1);
        $this->assertSame(1.5, (float) app(LandholdingAreaValidationService::class)
            ->forLandowner($landowner)['current_active_total']);
    }

    public function test_source_package_without_landholding_evidence_cannot_be_converted(): void
    {
        [$staff, $package] = $this->fixture();
        $package->records()->delete();
        $this->actingAs($staff)->post(route('staff.source-record-packages.landholding.store', $package), $this->input($package))
            ->assertSessionHasErrors('conversion');
        $this->assertDatabaseCount('landholdings', 0);
    }

    public function test_parcel_creation_with_landowner_selection_does_not_implicitly_activate_landholding(): void
    {
        [$staff, $package, $landowner] = $this->fixture();
        $package->update(['parcel_id' => null, 'landowner_id' => null]);
        $package = $package->fresh();

        $this->actingAs($staff)->post(route('staff.source-record-packages.create-parcel', $package), [
            'expected_record_revision' => $package->record_revision,
            'parcel_code' => 'REVIEW-NEW-PARCEL',
            'title_no' => 'TCT-REVIEW-NEW',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'area_hectares' => '3.0000',
            'geometry_geojson' => null,
            'status' => 'active',
            'remarks' => null,
            'landowner_id' => $landowner->id,
        ])->assertRedirect(route('staff.source-record-packages.show', $package));

        $this->assertDatabaseCount('landholdings', 0);
        $this->assertSame(0.0, (float) app(LandholdingAreaValidationService::class)
            ->forLandowner($landowner)['current_active_total']);
        $this->assertSame($landowner->id, $package->fresh()->landowner_id);
        $this->assertNotNull($package->fresh()->parcel_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'parcel_created_from_source_record_package']);
    }

    public function test_historical_source_linked_during_parcel_creation_does_not_activate_hectares(): void
    {
        [$staff, $package, $landowner] = $this->fixture(LegacyRecord::SOURCE_SCOPE_HISTORICAL);
        $package->update(['parcel_id' => null, 'landowner_id' => null]);
        $package = $package->fresh();

        $this->actingAs($staff)->post(route('staff.source-record-packages.create-parcel', $package), [
            'expected_record_revision' => $package->record_revision,
            'parcel_code' => 'REVIEW-HIST-PARCEL',
            'title_no' => null,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'area_hectares' => '3.0000',
            'geometry_geojson' => null,
            'status' => 'active',
            'remarks' => null,
            'landowner_id' => $landowner->id,
        ])->assertRedirect();

        $this->assertDatabaseCount('landholdings', 0);
        $this->actingAs($staff)->post(route('staff.source-record-packages.landholding.store', $package), $this->input($package->fresh()))
            ->assertRedirect();
        $this->assertSame(Landholding::STATUS_HISTORICAL, Landholding::firstOrFail()->status);
        $this->assertSame(0.0, (float) app(LandholdingAreaValidationService::class)
            ->forLandowner($landowner)['current_active_total']);
    }

    private function fixture(string $scope = LegacyRecord::SOURCE_SCOPE_CURRENT_ACTIVE): array
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true]);
        $landowner = Landowner::create([
            'first_name' => 'Review',
            'last_name' => 'Owner',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
        ]);
        $parcel = Parcel::create([
            'parcel_code' => 'REVIEW-PARCEL-001',
            'area_hectares' => 5.0000,
            'status' => 'active',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
        ]);
        $package = SourceRecordPackage::create([
            'package_code' => 'REVIEW-PACKAGE-001',
            'status' => SourceRecordPackage::STATUS_LINKED,
            'source_record_scope' => $scope,
            'landowner_id' => $landowner->id,
            'parcel_id' => $parcel->id,
            'landowner_name' => 'Review Owner',
            'parcel_code' => $parcel->parcel_code,
            'landholding_reference_number' => 'LH-REVIEW-001',
            'area_hectares' => 2.5000,
            'source_book' => 'Verified source book',
            'transcribed_by' => 'DAR Staff',
            'transcription_date' => now()->toDateString(),
            'encoded_by_user_id' => $staff->id,
        ]);
        LegacyRecord::create([
            'record_type' => LegacyRecord::TYPE_LANDHOLDING,
            'origin' => LegacyRecord::ORIGIN_IMPORTED,
            'source_record_scope' => $scope,
            'source_record_package_id' => $package->id,
            'landowner_id' => $landowner->id,
            'parcel_id' => $parcel->id,
            'landholding_reference_number' => 'LH-REVIEW-001',
            'source_book' => 'Verified source book',
            'transcribed_by' => 'DAR Staff',
            'transcription_date' => now()->toDateString(),
            'encoded_by_user_id' => $staff->id,
        ]);
        return [$staff, $package->fresh(), $landowner, $parcel];
    }

    private function input(SourceRecordPackage $package): array
    {
        return [
            'expected_record_revision' => $package->record_revision,
            'area_hectares' => '2.5000',
            'verified_source' => '1',
        ];
    }
}
