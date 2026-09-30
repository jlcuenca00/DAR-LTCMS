<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListScalingTest extends TestCase
{
    use RefreshDatabase;

    public function test_geodetic_reference_list_is_paginated(): void
    {
        $geodetic = $this->user(User::ROLE_GEODETIC);
        $owner = $this->landowner('Geodetic', 'Owner');

        $this->createLandholdings($owner, 25, 'GEO-LIST');

        $response = $this->actingAs($geodetic)
            ->get(route('geodetic.parcels.index'));

        $response->assertOk();
        $response->assertViewHas('landholdings', fn ($records) =>
            $records->total() === 25 && $records->count() === 20
        );

        $this->actingAs($geodetic)
            ->get(route('geodetic.parcels.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('landholdings', fn ($records) =>
                $records->total() === 25 && $records->count() === 5
            );
    }

    public function test_landowner_portal_lists_are_paginated_and_remain_isolated(): void
    {
        $landownerUser = $this->user(User::ROLE_LANDOWNER);
        $owner = $this->landowner('Portal', 'Owner', $landownerUser);
        $otherOwner = $this->landowner('Other', 'Owner');

        $this->createLandholdings($owner, 25, 'LO-LIST');
        $this->createLandholdings($otherOwner, 1, 'PRIVATE-LIST');

        $staff = $this->user(User::ROLE_STAFF);

        foreach (range(1, 16) as $sequence) {
            LandTransferApplication::create([
                'application_code' => sprintf('2026-PORTAL-%04d', $sequence),
                'transferor_landowner_id' => $owner->id,
                'transferor_name' => $owner->full_name,
                'transferors' => [[
                    'landowner_id' => $owner->id,
                    'name' => $owner->full_name,
                    'parcel_shares' => [],
                ]],
                'transferee_name' => 'Portal Transferee '.$sequence,
                'transferees' => [[
                    'landowner_id' => null,
                    'name' => 'Portal Transferee '.$sequence,
                    'parcel_shares' => [],
                ]],
                'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
                'encoded_by' => $staff->id,
            ]);
        }

        $parcelResponse = $this->actingAs($landownerUser)
            ->get(route('landowner.parcels.index'));

        $parcelResponse->assertOk();
        $parcelResponse->assertViewHas('landholdings', fn ($records) =>
            $records->total() === 25 && $records->count() === 20
        );
        $parcelResponse->assertDontSee('PRIVATE-LIST');

        $applicationResponse = $this->actingAs($landownerUser)
            ->get(route('landowner.applications.index'));

        $applicationResponse->assertOk();
        $applicationResponse->assertViewHas('applications', fn ($records) =>
            $records->total() === 16 && $records->count() === 15
        );

        $this->actingAs($landownerUser)
            ->get(route('landowner.applications.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('applications', fn ($records) =>
                $records->total() === 16 && $records->count() === 1
            );
    }

    public function test_staff_landowner_lookup_is_bounded_but_can_find_records_beyond_first_window(): void
    {
        $staff = $this->user(User::ROLE_STAFF);

        foreach (range(1, 25) as $sequence) {
            $this->landowner('Lookup', sprintf('Needle%02d', $sequence));
        }

        $this->actingAs($staff)
            ->getJson(route('staff.lookups.landowners'))
            ->assertOk()
            ->assertJsonCount(20, 'results');

        $target = Landowner::query()->where('last_name', 'Needle25')->firstOrFail();

        $this->actingAs($staff)
            ->getJson(route('staff.lookups.landowners', ['q' => 'Needle25']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $target->id);
    }

    public function test_staff_parcel_lookup_is_bounded_and_active_scope_is_enforced(): void
    {
        $staff = $this->user(User::ROLE_STAFF);

        foreach (range(1, 25) as $sequence) {
            $this->parcel(sprintf('PAR-LOOKUP-%03d', $sequence));
        }

        $inactive = $this->parcel('PAR-INACTIVE-999', 'inactive');

        $this->actingAs($staff)
            ->getJson(route('staff.lookups.parcels', ['scope' => 'active']))
            ->assertOk()
            ->assertJsonCount(20, 'results');

        $target = Parcel::query()->where('parcel_code', 'PAR-LOOKUP-025')->firstOrFail();

        $this->actingAs($staff)
            ->getJson(route('staff.lookups.parcels', [
                'scope' => 'active',
                'q' => 'PAR-LOOKUP-025',
            ]))
            ->assertOk()
            ->assertJsonPath('results.0.id', $target->id);

        $this->actingAs($staff)
            ->getJson(route('staff.lookups.parcels', [
                'scope' => 'active',
                'q' => $inactive->parcel_code,
            ]))
            ->assertOk()
            ->assertJsonCount(0, 'results');
    }

    public function test_user_link_lookup_returns_only_eligible_landowners(): void
    {
        $staff = $this->user(User::ROLE_STAFF);
        $linkedUser = $this->user(User::ROLE_LANDOWNER);

        $eligible = $this->landowner('Eligible', 'Owner');
        $linked = $this->landowner('Already', 'Linked', $linkedUser);

        $response = $this->actingAs($staff)
            ->getJson(route('staff.lookups.landowners', [
                'scope' => 'user-link',
                'q' => 'Owner',
            ]));

        $response->assertOk();
        $this->assertContains($eligible->id, collect($response->json('results'))->pluck('id')->all());

        $this->actingAs($staff)
            ->getJson(route('staff.lookups.landowners', [
                'scope' => 'user-link',
                'q' => 'Already Linked',
            ]))
            ->assertOk()
            ->assertJsonCount(0, 'results');

        $this->actingAs($staff)
            ->getJson(route('staff.lookups.landowners', [
                'scope' => 'user-link',
                'current_user_id' => $linkedUser->id,
                'q' => 'Already Linked',
            ]))
            ->assertOk()
            ->assertJsonPath('results.0.id', $linked->id);
    }

    public function test_application_timeline_is_paginated_without_losing_history(): void
    {
        $staff = $this->user(User::ROLE_STAFF);

        $application = LandTransferApplication::create([
            'application_code' => '2026-TIMELINE-0001',
            'transferor_name' => 'Timeline Transferor',
            'transferors' => [[
                'landowner_id' => null,
                'name' => 'Timeline Transferor',
                'parcel_shares' => [],
            ]],
            'transferee_name' => 'Timeline Transferee',
            'transferees' => [[
                'landowner_id' => null,
                'name' => 'Timeline Transferee',
                'parcel_shares' => [],
            ]],
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        $rows = collect(range(1, 25))->map(function (int $sequence) use ($staff, $application) {
            $timestamp = now()->copy()->addSeconds($sequence);

            return [
                'event_uuid' => (string) Str::uuid(),
                'actor_user_id' => $staff->id,
                'actor_name_snapshot' => $staff->name,
                'actor_role_snapshot' => $staff->role,
                'land_transfer_application_id' => $application->id,
                'application_code_snapshot' => $application->application_code,
                'auditable_type' => LandTransferApplication::class,
                'auditable_id' => $application->id,
                'action' => 'timeline_event_'.$sequence,
                'metadata' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        })->all();

        DB::table('audit_logs')->insert($rows);

        $this->actingAs($staff)
            ->get(route('staff.applications.show', $application))
            ->assertOk()
            ->assertViewHas('applicationTimeline', fn ($timeline) =>
                $timeline->total() === 25 && $timeline->count() === 20
            );

        $this->actingAs($staff)
            ->get(route('staff.applications.show', [
                'application' => $application,
                'timeline_page' => 2,
            ]))
            ->assertOk()
            ->assertViewHas('applicationTimeline', fn ($timeline) =>
                $timeline->total() === 25 && $timeline->count() === 5
            );
    }

    public function test_lookup_endpoints_remain_staff_only(): void
    {
        $geodetic = $this->user(User::ROLE_GEODETIC);
        $landowner = $this->user(User::ROLE_LANDOWNER);

        $this->actingAs($geodetic)
            ->getJson(route('staff.lookups.landowners'))
            ->assertForbidden();

        $this->actingAs($landowner)
            ->getJson(route('staff.lookups.parcels'))
            ->assertForbidden();
    }

    private function createLandholdings(Landowner $owner, int $count, string $prefix): void
    {
        foreach (range(1, $count) as $sequence) {
            $parcel = $this->parcel(sprintf('%s-%03d', $prefix, $sequence));

            Landholding::create([
                'landowner_id' => $owner->id,
                'parcel_id' => $parcel->id,
                'area_hectares' => 1.0000,
                'status' => Landholding::STATUS_ACTIVE,
            ]);
        }
    }

    private function parcel(string $code, string $status = 'active'): Parcel
    {
        return Parcel::create([
            'parcel_code' => $code,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.0000,
            'status' => $status,
        ]);
    }

    private function landowner(string $firstName, string $lastName, ?User $user = null): Landowner
    {
        return Landowner::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'province' => 'Negros Oriental',
            'user_id' => $user?->id,
        ]);
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'registration_status' => User::REGISTRATION_APPROVED,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }
}
