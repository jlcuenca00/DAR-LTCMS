<?php

namespace Tests\Feature;

use App\Models\ApplicationDocument;
use App\Models\ApplicationParcel;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\RequiredDocument;
use App\Models\User;
use App\Services\ApplicationComplianceNoticeService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkflowDatabaseGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('These safeguards target production PostgreSQL.');
        }
    }

    public function test_raw_final_application_mutations_and_deletions_are_blocked(): void
    {
        foreach (['approved', 'not_approved', 'released', 'denied'] as $status) {
            [$application] = $this->fixture($status);
            $this->rejects(fn () => DB::table('land_transfer_applications')->where('id', $application->id)->update(['remarks' => 'Tampered']));
            $this->rejects(fn () => DB::table('land_transfer_applications')->where('id', $application->id)->delete());
            $this->assertNull($application->fresh()->remarks);
        }
    }

    public function test_raw_final_child_insert_update_delete_and_reassignment_are_blocked(): void
    {
        [$application, $parcel, $document] = $this->fixture('approved');
        [$editable] = $this->fixture('pending_legal_review');

        foreach ([$parcel, $document] as $child) {
            $table = $child->getTable();
            $this->rejects(fn () => DB::table($table)->where('id', $child->id)->delete());
            $this->rejects(fn () => DB::table($table)->where('id', $child->id)->update(['land_transfer_application_id' => $editable->id]));
            $attributes = $child->getAttributes();
            unset($attributes['id']);
            if ($table === 'application_parcels') {
                $attributes['parcel_id'] = null;
            }
            $this->rejects(fn () => DB::table($table)->insert($attributes));
            $this->assertSame($application->id, $child->fresh()->land_transfer_application_id);
        }
        $this->rejects(fn () => DB::table('application_parcels')->where('id', $parcel->id)->update(['area_hectares' => 2]));
        $this->rejects(fn () => DB::table('application_documents')->where('id', $document->id)->update(['remarks' => 'Tampered']));
    }

    public function test_bulk_child_writes_bump_revision_and_active_links_cannot_move(): void
    {
        [$application, $parcel, $document] = $this->fixture('pending_legal_review');
        [$other] = $this->fixture('pending_legal_review');
        $revision = $application->fresh()->workflow_revision;
        DB::table('application_documents')->where('id', $document->id)->update(['remarks' => 'Updated']);
        $this->assertSame($revision + 1, $application->fresh()->workflow_revision);
        $revision = $application->fresh()->workflow_revision;
        DB::table('application_parcels')->where('id', $parcel->id)->update(['area_hectares' => 1.25]);
        $this->assertSame($revision + 1, $application->fresh()->workflow_revision);
        $this->rejects(fn () => DB::table('application_documents')->where('id', $document->id)->update(['land_transfer_application_id' => $other->id]));
        $this->rejects(fn () => DB::table('land_transfer_applications')->where('id', $application->id)->update(['workflow_revision' => 1]));
        DB::table('application_documents')->where('id', $document->id)->delete();
        $this->assertSame($revision + 2, $application->fresh()->workflow_revision);
    }

    public function test_database_allows_complete_release_progression_but_not_skips_or_rewrites(): void
    {
        [$application, , , $staff] = $this->fixture('approved');
        $this->rejects(fn () => DB::table('land_transfer_applications')->where('id', $application->id)->update(['release_status' => 'released']));
        $this->rejects(fn () => DB::table('land_transfer_applications')->where('id', $application->id)->update(['release_status' => 'ready_for_release']));
        DB::table('land_transfer_applications')->where('id', $application->id)->update([
            'release_status' => 'ready_for_release',
            'ready_for_release_at' => now(),
        ]);
        $this->rejects(fn () => DB::table('land_transfer_applications')->where('id', $application->id)->update(['ready_for_release_at' => now()->addDay()]));
        DB::table('land_transfer_applications')->where('id', $application->id)->update([
            'release_status' => 'released',
            'released_at' => now(),
            'released_by' => $staff->id,
            'release_recipient_name' => 'Client',
            'date_of_clearance_release' => now()->toDateString(),
        ]);
        $this->rejects(fn () => DB::table('land_transfer_applications')->where('id', $application->id)->update(['release_recipient_name' => 'Different Client']));
        $this->assertSame('Client', $application->fresh()->release_recipient_name);
    }

    public function test_compliance_history_is_preserved_and_resolved_once(): void
    {
        [$application, , , $staff] = $this->fixture('pending_legal_review');
        $notice = app(ApplicationComplianceNoticeService::class)->create($application->fresh(), [
            'category' => 'other', 'other_category' => 'Verification', 'details' => 'Review required',
            'resume_status' => 'pending_legal_review', 'requested_by' => $staff->id,
            'requested_by_name_snapshot' => $staff->name, 'requested_at' => now(),
        ]);
        $this->rejects(fn () => DB::table('application_compliance_notices')->where('id', $notice->id)->delete());
        $this->rejects(fn () => DB::table('application_compliance_notices')->where('id', $notice->id)->update(['details' => 'Rewritten']));
        $this->rejects(fn () => DB::table('application_compliance_notices')->where('id', $notice->id)->update(['resolved_at' => now()]));
        app(ApplicationComplianceNoticeService::class)->resolve($notice, [
            'resolved_at' => now(), 'resolved_by' => $staff->id,
            'resolved_by_name_snapshot' => $staff->name, 'resolution_note' => 'Verified',
        ]);
        $this->rejects(fn () => DB::table('application_compliance_notices')->where('id', $notice->id)->update(['resolution_note' => 'Rewritten']));
        DB::table('land_transfer_applications')->where('id', $application->id)->update(['status' => 'approved']);
        $attributes = $notice->fresh()->getAttributes();
        unset($attributes['id']);
        $attributes['resolved_at'] = null;
        $attributes['resolved_by'] = null;
        $this->rejects(fn () => DB::table('application_compliance_notices')->insert($attributes));
    }

    private function rejects(callable $write): void
    {
        try {
            DB::transaction($write);
            $this->fail('Expected PostgreSQL workflow guard rejection.');
        } catch (QueryException $e) {
            $this->assertSame('P0001', $e->errorInfo[0]);
        }
    }

    private function fixture(string $status): array
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = LandTransferApplication::create([
            'application_code' => 'DB-GUARD-' . $staff->id,
            'transferor_name' => 'Transferor', 'transferee_name' => 'Transferee',
            'status' => 'pending_legal_review', 'encoded_by' => $staff->id,
        ]);
        $master = Parcel::create(['parcel_code' => 'DB-PARCEL-' . $staff->id, 'area_hectares' => 3, 'status' => 'active']);
        $parcel = ApplicationParcel::create([
            'land_transfer_application_id' => $application->id, 'parcel_id' => $master->id,
            'parcel_code' => $master->parcel_code, 'area_hectares' => 1,
        ]);
        $requirement = RequiredDocument::create([
            'name' => 'DB-Requirement-' . $staff->id, 'applies_to' => 'transferor', 'is_mandatory' => false,
        ]);
        $document = ApplicationDocument::create([
            'land_transfer_application_id' => $application->id, 'required_document_id' => $requirement->id,
            'file_path' => 'fixtures/db-guard.pdf', 'original_filename' => 'db-guard.pdf', 'uploaded_by' => $staff->id,
        ]);
        if ($status !== 'pending_legal_review') {
            DB::table('land_transfer_applications')->where('id', $application->id)->update(['status' => $status]);
        }

        return [$application->fresh(), $parcel, $document, $staff];
    }
}
