<?php

namespace Tests\Feature;

use App\Models\ApplicationParcel;
use App\Models\LandTransferApplication;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\SourceRecordPackageImportBatch;
use App\Models\SystemNotification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
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

    public function test_shared_notification_dropdown_renders_only_current_users_latest_notifications(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $otherUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        SystemNotification::create([
            'user_id' => $otherUser->id,
            'type' => 'other_user_notification',
            'title' => 'Other user private notification',
            'message' => 'This notification must never appear in another user dropdown.',
        ]);

        for ($index = 1; $index <= 6; $index++) {
            SystemNotification::forceCreate([
                'user_id' => $user->id,
                'type' => 'shared_dropdown_test',
                'title' => 'Dropdown notification ' . $index,
                'message' => 'Shared notification component test.',
                'created_at' => now()->addSeconds($index),
                'updated_at' => now()->addSeconds($index),
            ]);
        }

        $this->actingAs($user);

        $html = Blade::render('<x-notification-dropdown />');

        $this->assertStringContainsString('data-notification-dropdown', $html);
        $this->assertStringContainsString('notification-badge', $html);
        $this->assertStringContainsString('>6<', preg_replace('/\\s+/', '', $html));
        $this->assertStringNotContainsString('Other user private notification', $html);
        $this->assertStringNotContainsString('Dropdown notification 1', $html);

        foreach (range(2, 6) as $index) {
            $this->assertStringContainsString('Dropdown notification ' . $index, $html);
        }
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

    public function test_user_cannot_open_another_users_notification(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $otherUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $notification = SystemNotification::create([
            'user_id' => $owner->id,
            'type' => 'private_notification',
            'title' => 'Private notification',
            'message' => 'Only the assigned user may open this notification.',
        ]);

        $this->actingAs($otherUser)
            ->get(route('notifications.open', $notification))
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
            'type' => 'application_submitted',
            'title' => 'Application submitted for review',
            'message' => 'Application submitted for review.',
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

    public function test_internal_stage_advancement_notifies_linked_landowner_without_staff_noise(): void
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

        $this->linkSubjectParcel($application, 'APP-NOTIF-ADVANCE-PARCEL');

        $this->actingAs($staffUser)
            ->post(route('staff.applications.submit', $application))
            ->assertRedirect();

        $this->assertDatabaseMissing('system_notifications', [
            'user_id' => $staffUser->id,
            'type' => 'application_status_updated',
        ]);

        $this->assertDatabaseMissing('system_notifications', [
            'user_id' => $staffUser->id,
            'type' => 'application_submitted',
        ]);

        $this->assertDatabaseHas('system_notifications', [
            'user_id' => $landownerUser->id,
            'type' => 'landowner_application_status',
        ]);
    }

    public function test_legacy_submission_into_review_creates_staff_submitted_notification(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'APP-NOTIF-SUBMIT-001',
            'transferor_name' => 'Legacy Transferor',
            'transferee_name' => 'Legacy Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        $this->linkSubjectParcel($application, 'APP-NOTIF-SUBMIT-PARCEL');

        $this->actingAs($staffUser)
            ->post(route('staff.applications.submit', $application))
            ->assertRedirect();

        $this->assertDatabaseHas('system_notifications', [
            'user_id' => $staffUser->id,
            'type' => 'application_submitted',
            'title' => 'Application submitted for review',
        ]);
    }

    public function test_final_not_approved_decision_creates_staff_and_landowner_notifications(): void
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
            'status' => LandTransferApplication::STATUS_FOR_RELEASING,
            'encoded_by' => $staffUser->id,
        ]);

        $this->linkSubjectParcel($application, 'APP-NOTIF-FINAL-PARCEL');
        $application->forceFill([
            'payment_order_reference' => 'OP-APP-NOTIF-FINAL-001',
            'payment_order_issued_at' => now(),
            'or_number' => 'OR-APP-NOTIF-FINAL-001',
            'or_date' => now()->toDateString(),
            'amount_paid' => config('dar_ltc.filing_fee', 2000),
            'ltc_form4_subject_land_findings' => ['ra6657_not_covered_not_tenanted_retained_area'],
            'ltc_form4_recommendation_findings' => ['application_complete'],
            'ltc_form4_recommendation_decision' => 'denial',
            'ltc_form4_certified_at' => now()->toDateString(),
            'ltc_form4_certifying_officer_name' => 'Authorized Review Officer',
            'csw_reference' => 'CSW-APP-NOTIF-FINAL-001',
            'csw_completed_at' => now(),
            'csw_prepared_by' => $staffUser->id,
        ])->save();

        $this->actingAs($staffUser)
            ->post(route('staff.applications.not_approved', $application), [
                'final_decision_confirmation' => '1',
                'decision_reason' => 'Invalid transfer for DAR clearance processing',
                'decision_notes' => 'Test final decision notification.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('system_notifications', [
            'user_id' => $staffUser->id,
            'type' => 'application_not_approved',
        ]);

        $this->assertDatabaseHas('system_notifications', [
            'user_id' => $landownerUser->id,
            'type' => 'landowner_final_decision',
        ]);
    }

    public function test_landowner_notification_is_deduplicated_and_payload_is_minimal(): void
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
            'first_name' => 'Minimal',
            'last_name' => 'Payload',
            'province' => 'Negros Oriental',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'APP-NOTIF-MINIMAL-001',
            'transferor_name' => 'Minimal Payload',
            'transferee_name' => 'Minimal Payload',
            'transferor_landowner_id' => $landowner->id,
            'transferee_landowner_id' => $landowner->id,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_ENDORSED_LTI,
            'encoded_by' => $staffUser->id,
        ]);

        app(NotificationService::class)
            ->notifyLinkedLandownersStatusChanged($application, $application->statusLabel());

        $this->assertSame(1, SystemNotification::query()
            ->where('user_id', $landownerUser->id)
            ->where('type', 'landowner_application_status')
            ->count());

        $notification = SystemNotification::query()
            ->where('user_id', $landownerUser->id)
            ->where('type', 'landowner_application_status')
            ->firstOrFail();

        $this->assertSame($application->id, $notification->data['application_id']);
        $this->assertSame($application->application_code, $notification->data['application_code']);
        $this->assertSame($application->status, $notification->data['status']);
        $this->assertArrayNotHasKey('transferor_name', $notification->data);
        $this->assertArrayNotHasKey('transferee_name', $notification->data);
        $this->assertArrayNotHasKey('municipality', $notification->data);
        $this->assertArrayNotHasKey('barangay', $notification->data);
    }

    public function test_inactive_or_unlinked_landowners_do_not_receive_application_notifications(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $inactiveUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => false,
        ]);

        $inactiveLandowner = Landowner::create([
            'user_id' => $inactiveUser->id,
            'first_name' => 'Inactive',
            'last_name' => 'Linked',
            'province' => 'Negros Oriental',
        ]);

        $unlinkedUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        Landowner::create([
            'user_id' => $unlinkedUser->id,
            'first_name' => 'Active',
            'last_name' => 'Unlinked',
            'province' => 'Negros Oriental',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'APP-NOTIF-RECIPIENT-001',
            'transferor_name' => 'Inactive Linked',
            'transferee_name' => 'Inactive Linked',
            'transferor_landowner_id' => $inactiveLandowner->id,
            'transferee_landowner_id' => $inactiveLandowner->id,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_ENDORSED_LTI,
            'encoded_by' => $staffUser->id,
        ]);

        app(NotificationService::class)
            ->notifyLinkedLandownersStatusChanged($application, $application->statusLabel());

        $this->assertDatabaseMissing('system_notifications', [
            'user_id' => $inactiveUser->id,
            'type' => 'landowner_application_status',
        ]);

        $this->assertDatabaseMissing('system_notifications', [
            'user_id' => $unlinkedUser->id,
            'type' => 'landowner_application_status',
        ]);
    }

    public function test_committed_source_import_notifies_only_active_geodetic_users_once(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $activeGeodetic = User::factory()->create([
            'role' => User::ROLE_GEODETIC,
            'is_active' => true,
        ]);

        $inactiveGeodetic = User::factory()->create([
            'role' => User::ROLE_GEODETIC,
            'is_active' => false,
        ]);

        $batch = SourceRecordPackageImportBatch::create([
            'original_filename' => 'notification-import.csv',
            'status' => 'previewed',
            'uploaded_by_user_id' => $staffUser->id,
            'preview_rows' => [],
            'summary' => [],
        ]);

        $batch->update([
            'status' => 'committed',
            'committed_rows' => 3,
            'committed_by_user_id' => $staffUser->id,
            'committed_at' => now(),
        ]);

        $this->assertSame(1, SystemNotification::query()
            ->where('user_id', $activeGeodetic->id)
            ->where('type', 'geodetic_reference_imported')
            ->count());

        $this->assertDatabaseMissing('system_notifications', [
            'user_id' => $inactiveGeodetic->id,
            'type' => 'geodetic_reference_imported',
        ]);

        $this->assertDatabaseMissing('system_notifications', [
            'user_id' => $staffUser->id,
            'type' => 'geodetic_reference_imported',
        ]);
    }

    public function test_landowner_final_decision_and_release_notifications_have_distinct_targets(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $landownerUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'APP-NOTIF-TARGET-001',
            'transferor_name' => 'Target Transferor',
            'transferee_name' => 'Target Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_APPROVED,
            'encoded_by' => $staffUser->id,
        ]);

        $decisionNotification = SystemNotification::create([
            'user_id' => $landownerUser->id,
            'type' => 'landowner_final_decision',
            'title' => 'Final decision recorded',
            'message' => 'A final decision was recorded.',
            'related_type' => LandTransferApplication::class,
            'related_id' => $application->id,
        ]);

        $releaseNotification = SystemNotification::create([
            'user_id' => $landownerUser->id,
            'type' => 'landowner_clearance_released',
            'title' => 'Decision output released',
            'message' => 'The signed output was released.',
            'related_type' => LandTransferApplication::class,
            'related_id' => $application->id,
        ]);

        $this->assertSame(
            route('landowner.applications.index'),
            $decisionNotification->targetUrlFor($landownerUser)
        );
        $this->assertSame(
            route('landowner.applications.clearance.show', $application),
            $releaseNotification->targetUrlFor($landownerUser)
        );
    }

    public function test_landowner_parcel_notification_target_requires_landholding_link(): void
    {
        $landownerUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        $landowner = Landowner::create([
            'user_id' => $landownerUser->id,
            'first_name' => 'Parcel',
            'last_name' => 'Viewer',
            'province' => 'Negros Oriental',
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'PCL-NOTIF-TARGET-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'area_hectares' => 1.0000,
            'status' => 'active',
        ]);

        $notification = SystemNotification::create([
            'user_id' => $landownerUser->id,
            'type' => 'landowner_parcel_reference',
            'title' => 'Parcel reference',
            'message' => 'A parcel reference is available.',
            'related_type' => Parcel::class,
            'related_id' => $parcel->id,
        ]);

        $this->assertSame(route('notifications.index'), $notification->targetUrlFor($landownerUser));

        Landholding::create([
            'landowner_id' => $landowner->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 1.0000,
            'status' => Landholding::STATUS_ACTIVE,
        ]);

        $this->assertSame(route('landowner.parcels.show', $parcel), $notification->targetUrlFor($landownerUser));
    }

    private function linkSubjectParcel(LandTransferApplication $application, string $parcelCode): void
    {
        $parcel = Parcel::create([
            'parcel_code' => $parcelCode,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.0000,
            'status' => 'active',
        ]);

        ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'area_hectares' => 1.0000,
        ]);
    }
}
