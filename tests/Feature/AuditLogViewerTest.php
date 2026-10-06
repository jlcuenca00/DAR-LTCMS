<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LandTransferApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_and_login_history_keep_results_counts_options_and_print_separate(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        foreach (['user_login', 'user_logout', 'document_uploaded'] as $action) {
            AuditLog::create([
                'actor_user_id' => $staff->id, 'action' => $action,
                'metadata' => ['marker' => 'scope-'.$action],
            ]);
        }

        $activity = $this->actingAs($staff)->get(route('staff.audit-logs.index'));
        $activity->assertOk()->assertSee('scope-document_uploaded')
            ->assertDontSee('scope-user_login')->assertDontSee('scope-user_logout')
            ->assertViewHas('summary', fn ($summary) => $summary['matching_records'] === 1)
            ->assertViewHas('actions', fn ($actions) => $actions->all() === ['document_uploaded']);

        $logins = $this->get(route('staff.audit-logs.index', ['view' => 'logins']));
        $logins->assertOk()->assertSee('scope-user_login')->assertSee('scope-user_logout')
            ->assertDontSee('scope-document_uploaded')
            ->assertViewHas('summary', fn ($summary) => $summary['matching_records'] === 2)
            ->assertViewHas('actions', fn ($actions) => $actions->all() === ['user_login', 'user_logout']);

        $this->get(route('staff.audit-logs.print'))->assertOk()
            ->assertSee('Document Uploaded')->assertDontSee('User Login')->assertDontSee('User Logout')
            ->assertViewHas('auditLogs', fn ($logs) => $logs->pluck('action')->all() === ['document_uploaded']);
        $this->get(route('staff.audit-logs.print', ['view' => 'logins']))->assertOk()
            ->assertSee('Login History Report')->assertSee('User Login')
            ->assertSee('User Logout')->assertDontSee('Document Uploaded')
            ->assertViewHas('auditLogs', fn ($logs) => $logs->pluck('action')->sort()->values()->all() === ['user_login', 'user_logout']);

        $this->assertDatabaseCount('audit_logs', 3);
    }

    public function test_login_history_filters_pagination_and_scope_cannot_leak_activity(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        foreach (range(1, 16) as $index) {
            AuditLog::create([
                'actor_user_id' => $staff->id, 'actor_username_snapshot' => 'history.actor',
                'action' => 'user_login', 'metadata' => ['marker' => 'login-page-'.$index],
            ]);
        }
        AuditLog::create(['actor_user_id' => $staff->id, 'action' => 'document_uploaded']);

        $filters = ['view' => 'logins', 'actor' => 'history.actor', 'action' => 'user_login',
            'date_from' => today()->toDateString(), 'date_to' => today()->toDateString()];
        $this->actingAs($staff)->get(route('staff.audit-logs.index', $filters))->assertOk()
            ->assertViewHas('auditLogs', fn ($logs) => $logs->total() === 16 && $logs->count() === 15)
            ->assertSee('view=logins', false);
        $this->get(route('staff.audit-logs.index', $filters + ['page' => 2]))->assertOk()
            ->assertViewHas('auditLogs', fn ($logs) => $logs->total() === 16 && $logs->count() === 1);

        foreach ([['view' => 'activity', 'action' => 'user_login'], ['view' => 'logins', 'action' => 'document_uploaded']] as $conflict) {
            $this->get(route('staff.audit-logs.index', $conflict))->assertOk()
                ->assertViewHas('auditLogs', fn ($logs) => $logs->total() === 0);
            $this->get(route('staff.audit-logs.print', $conflict))->assertOk()
                ->assertViewHas('auditLogs', fn ($logs) => $logs->isEmpty());
        }
    }

    public function test_login_history_stays_staff_only_and_rejects_invalid_view(): void
    {
        foreach (['geodetic', 'landowner'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            foreach (['staff.audit-logs.index', 'staff.audit-logs.print'] as $route) {
                $this->actingAs($user)->get(route($route, ['view' => 'logins']))->assertForbidden();
            }
        }
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->getJson(route('staff.audit-logs.index', ['view' => 'all']))
            ->assertUnprocessable()->assertJsonValidationErrors('view');
    }

    public function test_staff_can_view_audit_log_viewer(): void
    {
        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'AUDIT-VIEW-001',
            'transferor_name' => 'Audit Transferor',
            'transferee_name' => 'Audit Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        AuditLog::create([
            'actor_user_id' => $staffUser->id,
            'actor_name_snapshot' => $staffUser->name,
            'actor_username_snapshot' => $staffUser->username,
            'actor_role_snapshot' => $staffUser->role,
            'application_code_snapshot' => $application->application_code,
            'action' => 'document_uploaded',
            'land_transfer_application_id' => $application->id,
            'auditable_type' => LandTransferApplication::class,
            'auditable_id' => $application->id,
            'metadata' => [
                'document_reference_number' => 'TCT-TEST-001',
                'required_document_name' => 'Electronic Copy of Title',
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.audit-logs.index'));

        $response->assertOk();
        $response->assertSee('Audit Log Viewer');
        $response->assertSee('System Activity History');
        $response->assertSee('Document Uploaded');
        $response->assertSee('AUDIT-VIEW-001');
        $response->assertSee('TCT-TEST-001');
        $response->assertSee($staffUser->name);
        $response->assertSee('@' . $staffUser->username);
        $response->assertSee($staffUser->role);
        $response->assertDontSee($staffUser->email);
    }

    public function test_staff_can_filter_audit_logs_by_action(): void
    {
        $staffUser = User::factory()->create([
            'role' => 'staff',
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'AUDIT-FILTER-001',
            'transferor_name' => 'Filter Transferor',
            'transferee_name' => 'Filter Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_DRAFT,
            'encoded_by' => $staffUser->id,
        ]);

        AuditLog::create([
            'actor_user_id' => $staffUser->id,
            'action' => 'document_uploaded',
            'land_transfer_application_id' => $application->id,
            'auditable_type' => LandTransferApplication::class,
            'auditable_id' => $application->id,
            'metadata' => [
                'document_reference_number' => 'VISIBLE-001',
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        AuditLog::create([
            'actor_user_id' => $staffUser->id,
            'action' => 'application_approved',
            'land_transfer_application_id' => $application->id,
            'auditable_type' => LandTransferApplication::class,
            'auditable_id' => $application->id,
            'metadata' => [
                'decision' => 'HIDDEN-APPROVED-DECISION',
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.audit-logs.index', [
                'action' => 'document_uploaded',
            ]));

        $response->assertOk();
        $response->assertSee('Showing 1 of 1 record');
        $response->assertSee('Document Uploaded');
        $response->assertSee('VISIBLE-001');

        // Do not assertDontSee('Application Approved') because it appears in the filter dropdown.
        // Instead, confirm the hidden approved log metadata is not shown in the filtered result table.
        $response->assertDontSee('HIDDEN-APPROVED-DECISION');
    }

    public function test_landowner_cannot_view_staff_audit_logs(): void
    {
        $landownerUser = User::factory()->create([
            'role' => 'landowner',
        ]);

        $response = $this->actingAs($landownerUser)
            ->get(route('staff.audit-logs.index'));

        $response->assertForbidden();
    }

    public function test_geodetic_cannot_view_staff_audit_logs(): void
    {
        $geodeticUser = User::factory()->create([
            'role' => 'geodetic',
        ]);

        $response = $this->actingAs($geodeticUser)
            ->get(route('staff.audit-logs.index'));

        $response->assertForbidden();
    }
}
