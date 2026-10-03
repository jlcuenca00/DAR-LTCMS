<?php

namespace Tests\Feature;

use App\Models\ApplicationParcel;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\User;
use App\Services\ApplicationPartyLinkReviewService;
use App\Services\EqualAreaAllocationService;
use App\Services\LandholdingAreaValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationPartyEditingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_party_link_form_cannot_replace_newer_links(): void
    {
        [$staff, $application, $owner] = $this->records();
        $payload = $this->payload($application);
        $newOwner = Landowner::create(['first_name' => 'New', 'last_name' => 'Owner']);
        $application->update(['transferors' => [['name' => 'Owner', 'landowner_id' => $newOwner->id]]]);
        $this->actingAs($staff)->patch(route('staff.applications.landowner-links.update', $application), $payload)
            ->assertSessionHasErrors('workflow_revision');
        $this->assertSame($newOwner->id, $application->fresh()->partyRows('transferor')[0]['landowner_id']);
    }

    public function test_stale_create_from_party_form_creates_no_landowner(): void
    {
        [$staff, $application] = $this->records();
        $revision = $application->fresh()->workflow_revision;
        $count = Landowner::count();
        $application->update(['remarks' => 'New reviewed data']);
        $this->actingAs($staff)->post(route('staff.applications.landowner-records.create', $application), [
            'party' => 'transferee', 'index' => 0, 'expected_workflow_revision' => $revision,
        ])->assertSessionHasErrors('workflow_revision');
        $this->assertSame($count, Landowner::count());
        $this->assertNull($application->fresh()->partyRows('transferee')[0]['landowner_id']);
    }

    public function test_sync_rejects_changed_shared_holding_without_application_revision_change(): void
    {
        [$staff, $application, , $parcel, $ap] = $this->records();
        $payload = $this->payload($application);
        $payload['sync_current_landholdings'] = true;
        $payload['transferors'][0]['parcel_shares'] = [$ap->id => 1];
        $holding = Landholding::create(['landowner_id' => $application->transferor_landowner_id,
            'parcel_id' => $parcel->id, 'area_hectares' => 1, 'status' => 'active',
            'source_application_id' => $application->id]);
        $payload['expected_party_dependency'] = app(ApplicationPartyLinkReviewService::class)->fingerprint($application);
        $revision = $application->fresh()->workflow_revision;
        $holding->update(['area_hectares' => 0.5]);
        $this->assertSame($revision, $application->fresh()->workflow_revision);
        $this->actingAs($staff)->patch(route('staff.applications.landowner-links.update', $application), $payload)
            ->assertSessionHasErrors('expected_party_dependency');
        $this->assertSame('0.5000', $holding->fresh()->area_hectares);
    }

    public function test_sync_rejects_changed_parcel_and_missing_review_tokens(): void
    {
        [$staff, $application, , $parcel, $ap] = $this->records();
        $payload = $this->payload($application);
        $payload['sync_current_landholdings'] = true;
        $payload['transferors'][0]['parcel_shares'] = [$ap->id => 1];
        $parcel->update(['area_hectares' => 2]);
        $this->actingAs($staff)->patch(route('staff.applications.landowner-links.update', $application), $payload)
            ->assertSessionHasErrors('expected_party_dependency');
        $payload['expected_party_dependency'] = app(ApplicationPartyLinkReviewService::class)->fingerprint($application);
        unset($payload['expected_workflow_revision']);
        $this->patch(route('staff.applications.landowner-links.update', $application), $payload)
            ->assertSessionHasErrors('expected_workflow_revision');
        $payload = $this->payload($application);
        $payload['sync_current_landholdings'] = true;
        unset($payload['expected_party_dependency']);
        $this->patch(route('staff.applications.landowner-links.update', $application), $payload)
            ->assertSessionHasErrors('expected_party_dependency');
        $this->assertSame(0, Landholding::count());
    }

    public function test_intake_rejects_nested_share_and_unknown_party_fields(): void
    {
        [$staff] = $this->records();
        $count = LandTransferApplication::count();
        foreach (['transferors', 'transferees'] as $party) {
            foreach (['parcel_shares' => ['9999' => -1], 'unexpected_field' => 'Unvalidated'] as $key => $value) {
                $payload = ['transferors' => [['name' => 'Seller']], 'transferees' => [['name' => 'Buyer']]];
                $payload[$party][0][$key] = $value;
                $this->actingAs($staff)->post(route('staff.applications.store'), $payload)
                    ->assertSessionHasErrors($party . '.0');
                $this->assertSame($count, LandTransferApplication::count());
            }
        }
        $this->post(route('staff.applications.store'), [
            'transferors' => [['name' => 'Seller']], 'transferees' => [['name' => 'Buyer']],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($count + 1, LandTransferApplication::count());
        $this->assertSame([], LandTransferApplication::latest('id')->first()->partyRows('transferee')[0]['parcel_shares']);
    }

    public function test_party_share_keys_must_belong_to_current_application_parcels(): void
    {
        [$staff, $application] = $this->records();
        foreach (['transferors', 'transferees'] as $party) {
            $payload = $this->payload($application);
            $payload[$party][0]['parcel_shares'] = ['999999' => 1];
            $this->actingAs($staff)->patch(route('staff.applications.landowner-links.update', $application), $payload)
                ->assertSessionHasErrors($party . '.0.parcel_shares');
        }
    }

    public function test_implicit_equal_shares_conserve_area_and_match_explicit_equal_split(): void
    {
        [$staff, $application, $owner, , $ap] = $this->records();
        $buyers = collect(range(1, 3))->map(fn ($i) => Landowner::create(['first_name' => 'Buyer', 'last_name' => (string) $i]));
        $application->update(['transferees' => $buyers->map(fn ($buyer) => ['name' => $buyer->full_name, 'landowner_id' => $buyer->id])->all()]);
        $application = $application->fresh();
        $fallback = $buyers->map(fn ($buyer) => $application->partyAreaForParcel('transferee', $buyer->id, $ap->id, 1))->all();
        $this->assertSame([0.3333, 0.3333, 0.3334], $fallback);
        $this->assertSame(1.0, round(array_sum($fallback), 4));
        $payload = $this->payload($application);
        $payload['split_equally'] = true;
        $this->actingAs($staff)->patch(route('staff.applications.landowner-links.update', $application), $payload)
            ->assertSessionHasNoErrors()->assertRedirect();
        $explicit = collect($application->fresh()->partyRows('transferee'))->map(fn ($row) => $row['parcel_shares'][$ap->id])->all();
        $this->assertSame($fallback, $explicit);
        $this->assertSame([0.0, 0.0, 0.0001], app(EqualAreaAllocationService::class)->allocate(0.0001, 3));
    }

    public function test_rounding_remainder_is_included_in_hectare_threshold_checks(): void
    {
        [, $application, , , $ap] = $this->records();
        $buyers = collect(range(1, 3))->map(function ($i) {
            $buyer = Landowner::create(['first_name' => 'Buyer', 'last_name' => (string) $i]);
            $parcel = Parcel::create(['parcel_code' => 'CURRENT-' . $buyer->id, 'area_hectares' => 5, 'status' => 'active']);
            Landholding::create(['landowner_id' => $buyer->id, 'parcel_id' => $parcel->id, 'area_hectares' => 4.6667, 'status' => 'active']);
            return $buyer;
        });
        $application->update(['transferees' => $buyers->map(fn ($buyer) => ['name' => $buyer->full_name, 'landowner_id' => $buyer->id])->all()]);
        $result = app(LandholdingAreaValidationService::class)->forApplication($application->fresh());
        $this->assertTrue($result['blocks_release']);
        $last = collect($result['per_landowner'])->firstWhere('landowner_id', $buyers->last()->id);
        $this->assertSame(5.0001, $last['projected_total']);
    }

    private function records(): array
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true]);
        $owner = Landowner::create(['first_name' => 'Original', 'last_name' => 'Owner']);
        $application = LandTransferApplication::create([
            'application_code' => 'PARTY-EDIT-' . $owner->id, 'status' => 'pending_legal_review',
            'transferors' => [['name' => 'Owner', 'landowner_id' => $owner->id]],
            'transferees' => [['name' => 'Buyer', 'landowner_id' => null]], 'encoded_by' => $staff->id,
        ]);
        $parcel = Parcel::create(['parcel_code' => 'PARTY-PARCEL-' . $owner->id, 'area_hectares' => 1, 'status' => 'active']);
        $ap = ApplicationParcel::create(['land_transfer_application_id' => $application->id, 'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code, 'area_hectares' => 1]);
        return [$staff, $application->fresh(), $owner, $parcel, $ap];
    }

    private function payload(LandTransferApplication $application): array
    {
        $application = $application->fresh();
        return ['transferors' => $application->partyRows('transferor'), 'transferees' => $application->partyRows('transferee'),
            'expected_workflow_revision' => $application->workflow_revision,
            'expected_party_dependency' => app(ApplicationPartyLinkReviewService::class)->fingerprint($application)];
    }
}
