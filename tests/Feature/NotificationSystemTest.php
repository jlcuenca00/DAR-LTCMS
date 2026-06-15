<?php

namespace Tests\Feature;

use App\Models\LandTransferApplication;
use App\Models\Landowner;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_notifications(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        SystemNotification::create([
            'user_id' => $user->id,
            'type' => 'application_created',
            'title' => 'Clearance application encoded',
            'message' => 'A clearance application was encoded: APP-TEST-001.',
        ]);

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('System Notifications');
        $response->assertSee('Clearance application encoded');
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        $otherUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        $notification = SystemNotification::create([
            'user_id' => $owner->id,
            'type' => 'landowner_application_status',
            'title' => 'Application status updated',
            'message' => 'Your clearance application status was updated.',
        ]);

        $this->actingAs($otherUser)
            ->patch(route('notifications.read', $notification))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_clicking_notification_opens_related_page_and_marks_it_read(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'APP-NOTIF-OPEN-001',
            'transferor_name' => 'Open Transferor',
            'transferee_name' => 'Open Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staffUser->id,
        ]);

        $notification = SystemNotification::create([
            'user_id' => $staffUser->id,
            'type' => 'application_created',
            'title' => 'Clearance application encoded',
            'message' => 'A clearance application was encoded: APP-NOTIF-OPEN-001.',
            'related_type' => LandTransferApplication::class,
            'related_id' => $application->id,
        ]);

        $this->actingAs($staffUser)
            ->get(route('notifications.open', $notification))
            ->assertRedirect(route('staff.applications.show', $application));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_GEODETIC,
            'is_active' => true,
        ]);

        $notification = SystemNotification::create([
            'user_id' => $user->id,
            'type' => 'geodetic_reference_available',
            'title' => 'Source reference available for review',
            'message' => 'A source package is available for review.',
        ]);

        $this->actingAs($user)
            ->patch(route('notifications.read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        SystemNotification::create([
            'user_id' => $user->id,
            'type' => 'application_created',
            'title' => 'Clearance application encoded',
            'message' => 'Application encoded.',
        ]);

        SystemNotification::create([
            'user_id' => $user->id,
            'type' => 'application_status_updated',
            'title' => 'Application status updated',
            'message' => 'Application status updated.',
        ]);

        $this->actingAs($user)
            ->patch(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadSystemNotifications()->count());
    }

    public function test_staff_application_encoding_creates_staff_notification(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staffUser)
            ->post(route('staff.applications.store'), [
                'transferor_name' => 'Encoded Transferor',
                'transferee_name' => 'Encoded Transferee',
                'municipality' => 'Dumaguete City',
                'barangay' => 'Bantayan',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('system_notifications', [
            'user_id' => $staffUser->id,
            'type' => 'application_created',
            'title' => 'Clearance application encoded',
        ]);
    }

    public function test_application_stage_advancement_creates_staff_and_landowner_notifications(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $landownerUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        $landowner = Landowner::create([
            'user_id' => $landownerUser->id,
            'first_name' => 'Linked',
            'last_name' => 'Landowner',
            'province' => 'Negros Oriental',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'APP-NOTIF-ADVANCE-001',
            'transferor_name' => 'Linked Landowner',
            'transferee_name' => 'Linked Landowner',
            'transferor_landowner_id' => $landowner->id,
            'transferee_landowner_id' => $landowner->id,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staffUser->id,
        ]);

        $this->actingAs($staffUser)
            ->post(route('staff.applications.submit', $application))
            ->assertRedirect();

        $this->assertDatabaseHas('system_notifications', [
            'user_id' => $staffUser->id,
            'type' => 'application_status_updated',
        ]);

        $this->assertDatabaseHas('system_notifications', [
            'user_id' => $landownerUser->id,
            'type' => 'landowner_application_status',
        ]);
    }

    public function test_final_denied_decision_creates_staff_and_landowner_notifications(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $landownerUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        $landowner = Landowner::create([
            'user_id' => $landownerUser->id,
            'first_name' => 'Decision',
            'last_name' => 'Landowner',
            'province' => 'Negros Oriental',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'APP-NOTIF-FINAL-001',
            'transferor_name' => 'Decision Landowner',
            'transferee_name' => 'Decision Landowner',
            'transferor_landowner_id' => $landowner->id,
            'transferee_landowner_id' => $landowner->id,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staffUser->id,
        ]);

        $this->actingAs($staffUser)
            ->post(route('staff.applications.not_approved', $application), [
                'decision_reason' => 'Invalid transfer for DAR clearance processing',
                'decision_notes' => 'Test final decision notification.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('system_notifications', [
            'user_id' => $staffUser->id,
            'type' => 'application_denied',
        ]);

        $this->assertDatabaseHas('system_notifications', [
            'user_id' => $landownerUser->id,
            'type' => 'landowner_final_decision',
        ]);
    }
}
