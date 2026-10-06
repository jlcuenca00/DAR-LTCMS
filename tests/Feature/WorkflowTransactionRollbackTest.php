<?php

namespace Tests\Feature;

use App\Models\ApplicationClearance;
use App\Models\AuditLog;
use App\Models\LandTransferApplication;
use App\Models\SystemNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class WorkflowTransactionRollbackTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CreatesWorkflowIntegrityFixtures;

    public static function failureCases(): array
    {
        $cases = [];
        foreach (['approve', 'compliance.request', 'compliance.resolve', 'ready_for_release', 'release'] as $operation) {
            foreach (['audit', 'notification'] as $failure) {
                $cases[$operation.' '.$failure] = [$operation, $failure];
            }
        }
        $cases['approve clearance'] = ['approve', 'clearance'];
        return $cases;
    }

    #[DataProvider('failureCases')]
    public function test_workflow_failure_rolls_back_all_domain_writes(string $operation, string $failure): void
    {
        $staff = $this->workflowStaff();
        $application = $this->workflowApplication($staff, 'ATOMIC-ROLLBACK');
        $this->actingAs($staff);
        if ($operation === 'compliance.resolve') {
            $this->post(route('staff.applications.compliance.request', $application), $this->payload($application, 'compliance.request'))->assertSessionHas('success');
        }
        if (in_array($operation, ['ready_for_release', 'release'], true)) {
            $this->post(route('staff.applications.approve', $application), $this->approvalPayload($application))->assertSessionHas('success');
        }
        if ($operation === 'release') {
            $this->post(route('staff.applications.ready_for_release', $application), $this->payload($application, 'ready_for_release'))->assertSessionHas('success');
        }
        $application->refresh();
        $payload = $this->payload($application, $operation);
        $before = $this->snapshot($application);
        $model = match ($failure) {
            'audit' => AuditLog::class,
            'notification' => SystemNotification::class,
            'clearance' => ApplicationClearance::class,
        };
        // Fail after the actual insert, proving that partial writes are removed.
        $event = 'eloquent.created: '.$model;
        $injected = false;
        Event::listen($event, function ($record) use (&$injected, $application, $before): void {
            if ($record instanceof AuditLog && ! in_array($record->action, ['application_approved', 'clearance_generated', 'application_compliance_requested', 'application_compliance_resolved', 'application_ready_for_release', 'application_released_to_client'], true)) {
                return;
            }
            $injected = true;
            $this->assertNotSame($before['application'], (array) DB::table('land_transfer_applications')->find($application->id));
            throw new RuntimeException('Forced workflow transaction failure.');
        });
        try {
            if ($operation === 'ready_for_release') {
                $this->withoutExceptionHandling();
                try {
                    $this->post(route('staff.applications.'.$operation, $application), $payload);
                    $this->fail('Expected the release-readiness exception to propagate.');
                } catch (RuntimeException $exception) {
                    $this->assertSame('Forced workflow transaction failure.', $exception->getMessage());
                }
            } else {
                $this->post(route('staff.applications.'.$operation, $application), $payload)
                    ->assertRedirect()->assertSessionHas('error');
            }
        } finally {
            Event::forget($event);
        }
        $this->assertTrue($injected, 'The request must reach the intended failure boundary.');
        $this->assertSame($before, $this->snapshot($application), 'Status, revision, evidence, notices, clearances, domain audit, notifications and ownership must roll back together.');
    }

    private function payload(LandTransferApplication $application, string $operation): array
    {
        $application->refresh();
        $base = ['expected_status' => $application->status, 'expected_workflow_revision' => $application->workflow_revision];
        return match ($operation) {
            'approve' => $this->approvalPayload($application),
            'compliance.request' => $base + ['category' => 'clarification', 'details' => 'Atomic compliance test.'],
            'compliance.resolve' => $base + ['compliance_notice_id' => $application->activeComplianceNotice()->value('id'), 'resolution_note' => 'Atomic resolution.'],
            'ready_for_release' => $base,
            'release' => $base + ['release_confirmation' => '1', 'release_recipient_name' => 'Atomic Recipient', 'release_logbook_reference' => 'ATOMIC-LOG', 'csm_status' => 'received'],
        };
    }

    private function snapshot(LandTransferApplication $application): array
    {
        $rows = fn (string $table) => DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        return [
            'application' => (array) DB::table('land_transfer_applications')->find($application->id),
            'notices' => $rows('application_compliance_notices'), 'clearances' => $rows('application_clearances'),
            // Request-level fallback audit records may correctly describe the failed attempt.
            'domain_audit' => DB::table('audit_logs')->where('land_transfer_application_id', $application->id)
                ->whereIn('action', ['application_approved', 'clearance_generated', 'application_compliance_requested', 'application_compliance_resolved', 'application_ready_for_release', 'application_released_to_client'])
                ->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'notifications' => $rows('system_notifications'), 'landowners' => $rows('landowners'),
            'parcels' => $rows('parcels'), 'holdings' => $rows('landholdings'),
        ];
    }
}
