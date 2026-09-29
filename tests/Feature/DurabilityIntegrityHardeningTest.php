<?php

namespace Tests\Feature;

use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\LandTransferApplication;
use App\Models\RequiredDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class DurabilityIntegrityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_landowner_creation_rolls_back_when_audit_write_fails(): void
    {
        $staff = $this->staff();
        $eventName = 'eloquent.creating: '.AuditLog::class;

        Event::listen($eventName, function (): void {
            throw new RuntimeException('Forced audit failure.');
        });

        try {
            $this->withoutExceptionHandling();

            $this->actingAs($staff)
                ->post(route('staff.records.landowners.store'), [
                    'first_name' => 'Atomic',
                    'last_name' => 'Rollback',
                    'province' => 'Negros Oriental',
                ]);

            $this->fail('Expected forced audit failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced audit failure.', $exception->getMessage());
        } finally {
            Event::forget($eventName);
        }

        $this->assertDatabaseMissing('landowners', [
            'first_name' => 'Atomic',
            'last_name' => 'Rollback',
        ]);
    }

    public function test_application_document_replacement_rolls_back_database_and_new_file_when_audit_fails(): void
    {
        $disk = (string) config('filesystems.default');
        Storage::fake($disk);

        $staff = $this->staff();
        $application = $this->application($staff, 'DOC-DURABILITY-001');
        $requiredDocument = RequiredDocument::forceCreate([
            'name' => 'Durability Requirement',
            'applies_to' => 'transferor',
            'is_mandatory' => true,
            'legal_basis' => 'Durability regression test',
        ]);

        $oldPath = "application-documents/{$application->id}/original.pdf";
        Storage::put($oldPath, 'original-file');

        $document = ApplicationDocument::create([
            'land_transfer_application_id' => $application->id,
            'required_document_id' => $requiredDocument->id,
            'original_filename' => 'original.pdf',
            'file_path' => $oldPath,
            'uploaded_by' => $staff->id,
            'remarks' => 'Original durable document.',
        ]);

        $eventName = 'eloquent.creating: '.AuditLog::class;
        Event::listen($eventName, function (): void {
            throw new RuntimeException('Forced document audit failure.');
        });

        try {
            $this->withoutExceptionHandling();

            $this->actingAs($staff)
                ->post(route('staff.applications.documents.store', [$application, $requiredDocument]), [
                    'file' => UploadedFile::fake()->create('replacement.pdf', 20, 'application/pdf'),
                    'remarks' => 'Replacement that must roll back.',
                ]);

            $this->fail('Expected forced document audit failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced document audit failure.', $exception->getMessage());
        } finally {
            Event::forget($eventName);
        }

        $document->refresh();

        $this->assertSame($oldPath, $document->file_path);
        $this->assertSame('original.pdf', $document->original_filename);
        Storage::assertExists($oldPath);

        $files = collect(Storage::allFiles("application-documents/{$application->id}"));
        $this->assertSame([$oldPath], $files->sort()->values()->all());
    }

    public function test_postgresql_historical_append_only_triggers_are_installed(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Database trigger assertion applies to the production PostgreSQL stack.');
        }

        $triggers = DB::table('pg_trigger')
            ->whereIn('tgname', [
                'audit_logs_append_only',
                'application_clearances_append_only',
                'parcel_geometry_revisions_append_only',
            ])
            ->where('tgisinternal', false)
            ->pluck('tgname')
            ->all();

        $this->assertContains('audit_logs_append_only', $triggers);
        $this->assertContains('application_clearances_append_only', $triggers);
        $this->assertContains('parcel_geometry_revisions_append_only', $triggers);
    }

    public function test_postgresql_historical_foreign_keys_restrict_destructive_parent_deletes(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Foreign-key metadata assertion applies to the production PostgreSQL stack.');
        }

        $expected = [
            'land_transfer_applications_encoded_by_foreign',
            'land_transfer_applications_csw_prepared_by_foreign',
            'land_transfer_applications_released_by_foreign',
            'application_documents_land_transfer_application_id_foreign',
            'application_documents_required_document_id_foreign',
            'application_documents_uploaded_by_foreign',
            'application_clearances_land_transfer_application_id_foreign',
            'parcel_geometry_revisions_parcel_id_foreign',
            'audit_logs_actor_user_id_foreign',
            'audit_logs_land_transfer_application_id_foreign',
        ];

        $constraints = DB::table('pg_constraint')
            ->whereIn('conname', $expected)
            ->pluck('confdeltype', 'conname');

        foreach ($expected as $constraint) {
            $this->assertSame(
                'r',
                $constraints[$constraint] ?? null,
                "Expected {$constraint} to use ON DELETE RESTRICT."
            );
        }
    }

    private function staff(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    private function application(User $staff, string $code): LandTransferApplication
    {
        return LandTransferApplication::create([
            'application_code' => $code,
            'transferor_name' => 'Durability Transferor',
            'transferee_name' => 'Durability Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);
    }
}
