<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditLogPrintPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_print_is_bounded_to_newest_five_hundred_matching_entries(): void
    {
        $staff = $this->staff();
        $base = Carbon::parse('2026-09-01 08:00:00');

        $rows = collect(range(1, 505))
            ->map(function (int $index) use ($staff, $base) {
                $createdAt = $base->copy()->addSeconds($index);

                return [
                    'event_uuid' => (string) Str::uuid(),
                    'actor_user_id' => $staff->id,
                    'actor_name_snapshot' => $staff->name,
                    'actor_role_snapshot' => $staff->role,
                    'auditable_type' => User::class,
                    'auditable_id' => $staff->id,
                    'action' => 'bulk_audit_'.$index,
                    'metadata' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            })
            ->all();

        DB::table('audit_logs')->insert($rows);

        $response = $this->actingAs($staff)
            ->get(route('staff.audit-logs.print'));

        $response->assertOk();
        $response->assertViewHas('auditLogs', fn ($logs) => $logs->count() === 500);
        $response->assertViewHas('printTruncated', true);
        $response->assertViewHas('printLimit', 500);
        $response->assertSee('Safe print limit applied.');
        $response->assertSee('Showing the newest 500 matching audit entries.');

        $response->assertViewHas('auditLogs', function ($logs) {
            return $logs->first()?->action === 'bulk_audit_505'
                && $logs->last()?->action === 'bulk_audit_6';
        });
    }

    public function test_audit_index_and_print_support_timestamp_range_filters(): void
    {
        $staff = $this->staff();

        $this->insertAudit($staff, 'before_window', '2026-09-09 23:59:59');
        $this->insertAudit($staff, 'inside_window', '2026-09-15 12:00:00');
        $this->insertAudit($staff, 'after_window', '2026-09-21 00:00:00');

        $filters = [
            'date_from' => '2026-09-10',
            'date_to' => '2026-09-20',
        ];

        $index = $this->actingAs($staff)
            ->get(route('staff.audit-logs.index', $filters));

        $index->assertOk();
        $index->assertViewHas('auditLogs', function ($logs) {
            return $logs->total() === 1
                && $logs->first()?->action === 'inside_window';
        });
        $index->assertSee('inside window');
        $index->assertDontSee('before window');
        $index->assertDontSee('after window');

        $print = $this->actingAs($staff)
            ->get(route('staff.audit-logs.print', $filters));

        $print->assertOk();
        $print->assertViewHas('auditLogs', function ($logs) {
            return $logs->count() === 1
                && $logs->first()?->action === 'inside_window';
        });
        $print->assertViewHas('printTruncated', false);
        $print->assertSee('Date From: 2026-09-10');
        $print->assertSee('Date To: 2026-09-20');
    }

    public function test_audit_date_filter_rejects_reversed_range(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)
            ->from(route('staff.audit-logs.index'))
            ->get(route('staff.audit-logs.index', [
                'date_from' => '2026-09-20',
                'date_to' => '2026-09-10',
            ]))
            ->assertRedirect(route('staff.audit-logs.index'))
            ->assertSessionHasErrors('date_to');
    }

    private function insertAudit(User $staff, string $action, string $createdAt): void
    {
        DB::table('audit_logs')->insert([
            'event_uuid' => (string) Str::uuid(),
            'actor_user_id' => $staff->id,
            'actor_name_snapshot' => $staff->name,
            'actor_role_snapshot' => $staff->role,
            'auditable_type' => User::class,
            'auditable_id' => $staff->id,
            'action' => $action,
            'metadata' => null,
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ]);
    }

    private function staff(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }
}
