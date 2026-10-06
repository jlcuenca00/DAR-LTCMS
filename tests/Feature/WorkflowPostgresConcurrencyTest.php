<?php

namespace Tests\Feature;

use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class WorkflowPostgresConcurrencyTest extends TestCase
{
    // Workers must see committed fixtures. RefreshDatabase's outer transaction cannot be used here.
    use DatabaseMigrations;
    use \Tests\Concerns\CreatesWorkflowIntegrityFixtures;

    private array $workers = [];

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('CI') !== 'true' || DB::connection()->getDriverName() !== 'pgsql'
            || ! in_array(DB::connection()->getConfig('host'), ['127.0.0.1', 'localhost'], true)) {
            $this->markTestSkipped('Independent-process races run against isolated CI PostgreSQL.');
        }
    }

    protected function tearDown(): void
    {
        try {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($this->workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop(0);
                }
            }
        } finally {
            parent::tearDown();
        }
    }

    public static function competingOperations(): array
    {
        return [['approve'], ['requestCompliance'], ['release']];
    }

    #[DataProvider('competingOperations')]
    public function test_competing_requests_commit_once_and_reject_the_stale_request(string $method): void
    {
        $staff = $this->workflowStaff();
        $application = $this->workflowApplication($staff, 'ATOMIC-RACE');
        $this->actingAs($staff);
        if ($method === 'release') {
            $this->post(route('staff.applications.approve', $application), $this->approvalPayload($application))->assertSessionHas('success');
            $this->post(route('staff.applications.ready_for_release', $application), ['expected_workflow_revision' => $application->fresh()->workflow_revision])->assertSessionHas('success');
        }
        $application->refresh();
        $payload = match ($method) {
            'approve' => $this->approvalPayload($application),
            'requestCompliance' => ['expected_status' => $application->status, 'expected_workflow_revision' => $application->workflow_revision, 'category' => 'clarification', 'details' => 'Concurrent request.'],
            'release' => ['expected_workflow_revision' => $application->workflow_revision, 'release_confirmation' => '1', 'release_recipient_name' => 'Concurrent Recipient', 'csm_status' => 'received'],
        };
        $holdings = $this->holdings();
        DB::beginTransaction();
        DB::table('land_transfer_applications')->where('id', $application->id)->lockForUpdate()->first();
        $first = $this->worker($staff->id, $application->id, $method, $payload);
        $second = $this->worker($staff->id, $application->id, $method, $payload);
        $this->assertWorkersBlocked([$first, $second]);
        DB::commit();
        $results = [$this->result($first), $this->result($second)];
        $this->assertSame(1, count(array_filter($results, fn ($r) => $r['result'] === 'success')));
        $loser = array_values(array_filter($results, fn ($r) => $r['result'] !== 'success'))[0];
        $this->assertSame('validation', $loser['result']);
        $expectedField = match ($method) {
            'approve' => 'status', 'requestCompliance' => 'workflow_revision', 'release' => 'release',
        };
        $this->assertContains($expectedField, $loser['fields']);
        $action = match ($method) {
            'approve' => 'application_approved', 'requestCompliance' => 'application_compliance_requested', 'release' => 'application_released_to_client',
        };
        $this->assertSame(1, DB::table('audit_logs')->where('land_transfer_application_id', $application->id)->where('action', $action)->count());
        $this->assertSame($method === 'requestCompliance' ? 1 : 0, $application->complianceNotices()->count());
        $this->assertSame($method === 'requestCompliance' ? 0 : 1, $application->clearance()->count());
        $notificationTypes = match ($method) {
            'approve' => ['application_approved', 'landowner_final_decision'],
            'requestCompliance' => ['landowner_compliance_required'],
            'release' => ['application_released', 'landowner_clearance_released'],
        };
        foreach ($notificationTypes as $type) {
            $this->assertSame(1, DB::table('system_notifications')->where('related_type', LandTransferApplication::class)->where('related_id', $application->id)->where('type', $type)->count());
        }
        $this->assertSame($holdings, $this->holdings());
        $application->refresh();
        $this->assertSame($method === 'requestCompliance' ? LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE : LandTransferApplication::STATUS_APPROVED, $application->status);
        if ($method === 'release') {
            $this->assertSame(LandTransferApplication::RELEASED_TO_CLIENT, $application->release_status);
            $this->assertSame('Concurrent Recipient', $application->release_recipient_name);
        }
    }

    public static function sharedDependencies(): array
    {
        return [['parcel'], ['landowner']];
    }

    public function test_two_approvals_sharing_a_transferee_serialize_and_invalidate_the_second_snapshot(): void
    {
        $staff = $this->workflowStaff();
        $firstApplication = $this->workflowApplication($staff, 'ATOMIC-SHARED-A');
        $secondApplication = $this->workflowApplication($staff, 'ATOMIC-SHARED-B');
        $transferee = Landowner::findOrFail($firstApplication->transferee_landowner_id);
        $link = $secondApplication->applicationParcels()->firstOrFail();
        $secondApplication->forceFill([
            'transferee_landowner_id' => $transferee->id, 'transferee_name' => $transferee->full_name,
            'transferees' => [['name' => $transferee->full_name, 'landowner_id' => $transferee->id, 'parcel_shares' => [(string) $link->id => 1]]],
        ])->save();
        $firstPayload = $this->approvalPayload($firstApplication);
        $secondPayload = $this->approvalPayload($secondApplication);
        $holdings = $this->holdings();
        DB::beginTransaction();
        Landowner::whereKey($transferee->id)->lockForUpdate()->firstOrFail();
        $first = $this->worker($staff->id, $firstApplication->id, 'approve', $firstPayload);
        $second = $this->worker($staff->id, $secondApplication->id, 'approve', $secondPayload);
        $this->assertWorkersBlocked([$first, $second]);
        DB::commit();
        $results = [$this->result($first), $this->result($second)];
        $this->assertSame(1, count(array_filter($results, fn ($r) => $r['result'] === 'success')));
        $loser = array_values(array_filter($results, fn ($r) => $r['result'] !== 'success'))[0];
        $this->assertSame('validation', $loser['result']);
        $this->assertContains('workflow_dependency', $loser['fields']);
        $ids = [$firstApplication->id, $secondApplication->id];
        $this->assertSame(1, DB::table('application_clearances')->whereIn('land_transfer_application_id', $ids)->count());
        $this->assertSame(1, DB::table('audit_logs')->whereIn('land_transfer_application_id', $ids)->where('action', 'application_approved')->count());
        $this->assertSame(1, LandTransferApplication::whereIn('id', $ids)->where('status', LandTransferApplication::STATUS_APPROVED)->count());
        $this->assertSame(1, LandTransferApplication::whereIn('id', $ids)->where('status', LandTransferApplication::STATUS_FOR_RELEASING)->count());
        $this->assertSame($holdings, $this->holdings());
    }

    #[DataProvider('sharedDependencies')]
    public function test_approval_waits_for_shared_dependency_then_rechecks_committed_state(string $dependency): void
    {
        $staff = $this->workflowStaff();
        $application = $this->workflowApplication($staff, 'ATOMIC-DEPENDENCY');
        $payload = $this->approvalPayload($application);
        $before = (array) DB::table('land_transfer_applications')->find($application->id);
        $parcel = $application->applicationParcels()->firstOrFail()->parcel()->firstOrFail();
        $holdingParcel = $dependency === 'landowner' ? Parcel::create([
            'parcel_code' => 'ATOMIC-EXISTING-HOLDING', 'title_no' => 'T-ATOMIC-HOLDING',
            'municipality' => 'Dumaguete City', 'barangay' => 'Bantayan', 'province' => 'Negros Oriental',
            'area_hectares' => 5, 'area_square_meters' => 50000, 'status' => 'active',
        ]) : null;
        DB::beginTransaction();
        if ($dependency === 'parcel') {
            Parcel::whereKey($parcel->id)->lockForUpdate()->firstOrFail();
        } else {
            Landowner::whereKey($application->transferee_landowner_id)->lockForUpdate()->firstOrFail();
        }
        $worker = $this->worker($staff->id, $application->id, 'approve', $payload);
        $this->assertWorkersBlocked([$worker]);
        if ($dependency === 'parcel') {
            $parcel->forceFill(['area_hectares' => 2, 'area_square_meters' => 20000])->save();
        } else {
            // A newly committed holding would put this transferee beyond 5 ha.
            Landholding::create(['landowner_id' => $application->transferee_landowner_id, 'parcel_id' => $holdingParcel->id, 'area_hectares' => 5, 'status' => 'active']);
        }
        DB::commit();
        $holdings = $this->holdings();
        $result = $this->result($worker);
        $this->assertSame('validation', $result['result']);
        $this->assertContains('workflow_dependency', $result['fields']);
        $this->assertSame($before, (array) DB::table('land_transfer_applications')->find($application->id));
        $this->assertSame(0, $application->clearance()->count());
        $this->assertSame(0, DB::table('audit_logs')->where('land_transfer_application_id', $application->id)->where('action', 'application_approved')->count());
        $this->assertSame($holdings, $this->holdings());
    }

    private function worker(int $staffId, int $applicationId, string $method, array $payload): Process
    {
        $connection = DB::connection()->getConfig();
        $worker = new Process([PHP_BINARY, base_path('tests/Fixtures/workflow-concurrency-worker.php')], base_path(), [
            'CI' => 'true', 'APP_ENV' => 'testing', 'APP_KEY' => config('app.key'),
            'DB_CONNECTION' => 'pgsql', 'DB_HOST' => $connection['host'], 'DB_PORT' => (string) $connection['port'],
            'DB_DATABASE' => $connection['database'], 'DB_USERNAME' => $connection['username'], 'DB_PASSWORD' => $connection['password'],
            'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array', 'MAIL_MAILER' => 'array',
        ]);
        $worker->setInput(json_encode(['staff_id' => $staffId, 'application_id' => $applicationId, 'method' => $method, 'payload' => $payload], JSON_THROW_ON_ERROR));
        $worker->setTimeout(30);
        $worker->start();
        $this->workers[] = $worker;
        return $worker;
    }

    private function assertWorkersBlocked(array $workers): void
    {
        $deadline = microtime(true) + 15;
        do {
            $blocked = 0;
            DB::select('SELECT pg_stat_clear_snapshot()');
            foreach ($workers as $worker) {
                $line = strtok($worker->getOutput(), "\n");
                $pid = $line ? (json_decode($line, true)['pid'] ?? null) : null;
                if ($pid && DB::selectOne("SELECT EXISTS (SELECT 1 FROM pg_stat_activity WHERE pid = ? AND wait_event_type = 'Lock' AND cardinality(pg_blocking_pids(pid)) > 0) AS blocked", [$pid])->blocked) {
                    $blocked++;
                } else {
                    $this->assertTrue($worker->isRunning(), 'Worker exited before contending: '.$worker->getOutput().$worker->getErrorOutput());
                }
            }
            if ($blocked === count($workers)) {
                $this->assertSame(count($workers), $blocked, 'Real PostgreSQL lock contention was observed.');
                return;
            }
            usleep(20000);
        } while (microtime(true) < $deadline);
        $this->fail('Workers did not reach PostgreSQL lock contention before the deadline.');
    }

    private function result(Process $worker): array
    {
        $this->assertSame(0, $worker->wait(), $worker->getOutput().$worker->getErrorOutput());
        $lines = explode("\n", trim($worker->getOutput()));
        return json_decode(end($lines), true, 512, JSON_THROW_ON_ERROR);
    }

    private function holdings(): array
    {
        return DB::table('landholdings')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }
}
