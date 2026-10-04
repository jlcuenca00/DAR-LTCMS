<?php

namespace Tests\Feature;

use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\SourceRecordPackage;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationReadStateAndTargetTest extends TestCase
{
    use RefreshDatabase;

    private function notification(User $user): SystemNotification
    {
        return SystemNotification::create([
            'user_id' => $user->id,
            'type' => 'test_notice',
            'title' => 'Recent notice',
            'message' => 'Notice details',
        ]);
    }

    public function test_visible_read_leaves_unseen_notifications_unread_and_preserves_read_time(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true]);
        $notices = collect(range(1, 6))->map(fn () => $this->notification($user));
        $ids = $notices->take(5)->pluck('id')->all();

        $this->actingAs($user)->patchJson(route('notifications.read-visible'), ['notification_ids' => $ids])
            ->assertOk()->assertJson(['ok' => true, 'read_ids' => $ids, 'unread_count' => 1]);
        $this->assertNull($notices->last()->fresh()->read_at);
        $readAt = $notices->first()->fresh()->read_at->toISOString();
        $this->travel(5)->minutes();
        $this->patchJson(route('notifications.read-visible'), ['notification_ids' => $ids])
            ->assertOk()->assertJsonPath('unread_count', 1);
        $this->assertSame($readAt, $notices->first()->fresh()->read_at->toISOString());
    }

    public function test_visible_read_cannot_change_another_recipients_notification(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true]);
        $own = $this->notification($user);
        $foreign = $this->notification(User::factory()->create());
        $this->actingAs($user)->patchJson(route('notifications.read-visible'), [
            'notification_ids' => [$own->id, $foreign->id],
        ])->assertOk()->assertJson(['read_ids' => [$own->id], 'unread_count' => 0]);
        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_visible_read_rejects_oversized_empty_and_duplicate_batches(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true]);
        $notice = $this->notification($user);
        $this->actingAs($user);
        foreach ([[], range(1, 6), [$notice->id, $notice->id]] as $ids) {
            $this->patchJson(route('notifications.read-visible'), ['notification_ids' => $ids])
                ->assertUnprocessable();
        }
        $this->assertNull($notice->fresh()->read_at);
    }

    public function test_landowner_notification_focus_finds_older_application_without_exposing_foreign_records(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true]);
        $user = User::factory()->create(['role' => User::ROLE_LANDOWNER, 'is_active' => true]);
        $owner = Landowner::create([
            'user_id' => $user->id, 'first_name' => 'Focus', 'last_name' => 'Owner',
            'province' => 'Negros Oriental',
        ]);
        $applications = collect(range(1, 17))->map(fn ($i) => LandTransferApplication::forceCreate([
            'application_code' => 'APP-FOCUS-' . $i,
            'transferor_name' => 'Focus Owner', 'transferee_name' => 'Recipient',
            'transferor_landowner_id' => $i === 17 ? null : $owner->id,
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
            'created_at' => now()->addSeconds($i), 'updated_at' => now(),
        ]));
        $older = $applications->first();
        $foreign = $applications->last();
        $notice = $this->notification($user);
        $notice->update(['related_type' => LandTransferApplication::class, 'related_id' => $older->id]);
        $target = route('landowner.applications.index', ['application' => $older->id]) . '#application-' . $older->id;
        $this->actingAs($user)->get(route('landowner.applications.index'))
            ->assertOk()->assertDontSee('id="application-' . $older->id . '"', false);
        $this->get(route('notifications.open', $notice))->assertRedirect($target);
        $this->get($target)->assertOk()->assertSee('id="application-' . $older->id . '"', false)
            ->assertSee('View all applications')->assertDontSee($foreign->application_code);
        $this->get(route('landowner.applications.index', ['application' => $foreign->id]))
            ->assertOk()->assertSee('The selected application is not available for your account.')
            ->assertDontSee($foreign->application_code);
        $this->getJson(route('landowner.applications.index', ['application' => 'invalid']))
            ->assertUnprocessable();
    }

    public function test_geodetic_package_notice_targets_searchable_parcel_directory(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_GEODETIC, 'is_active' => true]);
        $notice = $this->notification($user);
        $notice->update([
            'related_type' => SourceRecordPackage::class, 'related_id' => 1,
            'data' => ['parcel_code' => 'PARCEL-UNLINKED'],
        ]);
        $target = route('geodetic.parcels.directory', ['q' => 'PARCEL-UNLINKED']);
        $this->actingAs($user)->get(route('notifications.open', $notice))->assertRedirect($target);
        $this->get($target)->assertOk();
        $notice->update(['data' => []]);
        $this->assertSame(route('geodetic.parcels.directory'), $notice->targetUrlFor($user));
    }
}
