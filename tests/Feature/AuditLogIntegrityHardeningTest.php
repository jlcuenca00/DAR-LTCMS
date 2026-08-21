<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LandTransferApplication;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use LogicException;
use Tests\TestCase;

class AuditLogIntegrityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_event_has_durable_event_request_actor_and_application_context(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $application = LandTransferApplication::create([
            'application_code' => 'AUDIT-CONTEXT-001',
            'transferor_name' => 'Context Transferor',
            'transferee_name' => 'Context Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        $this->actingAs($staff)
            ->post(route('staff.applications.submit', $application))
            ->assertSessionHas('success');

        $log = AuditLog::query()
            ->where('action', 'application_status_advanced')
            ->firstOrFail();

        $this->assertNotEmpty($log->event_uuid);
        $this->assertNotEmpty($log->request_id);
        $this->assertSame($staff->name, $log->actor_name_snapshot);
        $this->assertSame($staff->username, $log->actor_username_snapshot);
        $this->assertSame(User::ROLE_STAFF, $log->actor_role_snapshot);
        $this->assertSame($application->application_code, $log->application_code_snapshot);
        $this->assertSame('staff.applications.submit', $log->route_name);
        $this->assertSame('POST', $log->http_method);

        $this->assertDatabaseMissing('audit_logs', [
            'request_id' => $log->request_id,
            'action' => 'mutation_request_fallback',
        ]);
    }

    public function test_mutating_request_without_domain_event_receives_fallback_audit_record(): void
    {
        Route::middleware('web')
            ->patch('/_audit-integrity-probe', fn () => response()->noContent())
            ->name('audit.integrity.probe');

        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->actingAs($staff)
            ->patch(route('audit.integrity.probe'), [
                'safe_field' => 'safe-value',
                'password' => 'DO-NOT-STORE-THIS',
            ])
            ->assertNoContent();

        $log = AuditLog::query()
            ->where('action', 'mutation_request_fallback')
            ->firstOrFail();

        $this->assertSame($staff->id, $log->actor_user_id);
        $this->assertSame('audit.integrity.probe', $log->route_name);
        $this->assertSame('PATCH', $log->http_method);
        $this->assertSame(204, $log->metadata['response_status']);
        $this->assertSame(
            'fallback_for_mutating_request_without_domain_event',
            $log->metadata['audit_classification']
        );
        $this->assertContains('safe_field', $log->metadata['input_fields']);
        $this->assertStringNotContainsString('DO-NOT-STORE-THIS', json_encode($log->toArray(), JSON_THROW_ON_ERROR));
    }

    public function test_audit_logger_redacts_sensitive_metadata_recursively(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->actingAs($staff);

        $log = AuditLogger::record(
            'audit_redaction_probe',
            null,
            $staff,
            [
                'password' => 'SecretPassword123!',
                'must_change_password' => true,
                'nested' => [
                    'token' => 'secret-token-value',
                    'safe_reference' => 'REF-001',
                ],
            ]
        );

        $this->assertSame('[REDACTED]', $log->metadata['password']);
        $this->assertTrue($log->metadata['must_change_password']);
        $this->assertSame('[REDACTED]', $log->metadata['nested']['token']);
        $this->assertSame('REF-001', $log->metadata['nested']['safe_reference']);
    }

    public function test_audit_log_model_rejects_updates_and_deletes(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->actingAs($staff);
        $log = AuditLogger::record('audit_immutability_probe', null, $staff);

        try {
            $log->update(['action' => 'tampered_action']);
            $this->fail('Audit log update should have been rejected.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('append-only', $exception->getMessage());
        }

        $log->refresh();
        $this->assertSame('audit_immutability_probe', $log->action);

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_postgresql_append_only_trigger_is_installed(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Database trigger assertion applies to the production PostgreSQL stack.');
        }

        $exists = DB::table('pg_trigger')
            ->where('tgname', 'audit_logs_append_only')
            ->where('tgisinternal', false)
            ->exists();

        $this->assertTrue($exists);
    }
}
