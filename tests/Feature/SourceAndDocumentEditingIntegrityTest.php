<?php

namespace Tests\Feature;

use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\LegacyRecord;
use App\Models\Parcel;
use App\Models\RequiredDocument;
use App\Models\SourceRecordPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class SourceAndDocumentEditingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_document_save_and_delete_preserve_newer_evidence_and_file(): void
    {
        Storage::fake('local');
        [$staff, $application, $requirement] = $this->documentContext();
        $revision = $application->fresh()->workflow_revision;
        Storage::put('application-documents/current.pdf', 'current evidence');
        $document = ApplicationDocument::create([
            'land_transfer_application_id' => $application->id,
            'required_document_id' => $requirement->id,
            'file_path' => 'application-documents/current.pdf',
            'annex_reference' => 'Latest annex',
        ]);
        $url = route('staff.applications.documents.store', [$application, $requirement]);
        $this->actingAs($staff)->post($url, [
            'expected_workflow_revision' => $revision,
            'annex_reference' => 'Old annex',
            'file' => UploadedFile::fake()->create('stale.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('workflow_revision');
        $this->delete(route('staff.applications.documents.destroy', [$application, $requirement]), [
            'expected_workflow_revision' => $revision,
        ])->assertSessionHasErrors('workflow_revision');
        $this->assertSame('Latest annex', $document->fresh()->annex_reference);
        Storage::assertExists('application-documents/current.pdf');
        $this->assertCount(1, Storage::allFiles('application-documents'));
        $this->post($url, ['annex_reference' => 'Missing token'])->assertSessionHasErrors('expected_workflow_revision');
    }

    public function test_annex_and_source_only_evidence_remain_editable_and_count_in_checklist(): void
    {
        [$staff, $application, $requirement] = $this->documentContext();
        ApplicationDocument::create([
            'land_transfer_application_id' => $application->id,
            'required_document_id' => $requirement->id,
            'annex_reference' => 'Annex-only evidence',
        ]);
        $this->actingAs($staff)->get(route('staff.applications.show', $application))
            ->assertOk()->assertSee('1 / 1 required')->assertSee('Details saved')
            ->assertSee('document-edit-panel-'.$requirement->id, false)
            ->assertSee('name="expected_workflow_revision"', false);
        ApplicationDocument::firstOrFail()->update([
            'annex_reference' => null,
            'source_record_package_id' => $this->package($staff)->id,
        ]);
        $this->get(route('staff.applications.show', $application))->assertOk()
            ->assertSee('1 / 1 required')->assertSee('Details saved');
    }

    public function test_remarks_only_saved_row_can_be_edited_without_counting_as_evidence(): void
    {
        [$staff, $application, $requirement] = $this->documentContext();
        ApplicationDocument::create([
            'land_transfer_application_id' => $application->id,
            'required_document_id' => $requirement->id,
            'remarks' => 'Follow-up notes',
        ]);
        $this->actingAs($staff)->get(route('staff.applications.show', $application))->assertOk()
            ->assertSee('0 / 1 required')->assertSee('Notes only')
            ->assertSee('document-edit-panel-'.$requirement->id, false);
    }

    public function test_stale_package_actions_do_not_replace_files_links_or_create_records(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $staff = User::factory()->create(['role' => 'staff']);
        $package = $this->package($staff);
        $revision = $package->fresh()->record_revision;
        Storage::disk('local')->put('source-record-packages/current.pdf', 'current scan');
        $package->update(['source_file_path' => 'source-record-packages/current.pdf', 'remarks' => 'Newer']);
        $parcel = Parcel::create(['parcel_code' => 'LINK-TARGET', 'area_hectares' => 1, 'status' => 'active']);
        $owner = Landowner::create(['first_name' => 'Existing', 'last_name' => 'Owner']);
        $payloads = [
            ['post', 'link-parcel', ['parcel_id' => $parcel->id]],
            ['post', 'create-parcel', ['parcel_code' => 'SHOULD-NOT-CREATE', 'status' => 'active']],
            ['post', 'link-landowner', ['landowner_id' => $owner->id]],
            ['post', 'create-landowner', ['first_name' => 'ShouldNot', 'last_name' => 'Create']],
            ['post', 'source-file.store', ['source_file' => UploadedFile::fake()->create('stale.pdf', 10, 'application/pdf')]],
            ['delete', 'source-file.destroy', []],
            ['patch', 'update', ['landowner_name' => 'Stale']],
        ];
        foreach ($payloads as [$method, $route, $payload]) {
            $this->actingAs($staff)->{$method}(route('staff.source-record-packages.'.$route, $package),
                array_merge($payload, ['expected_record_revision' => $revision]))
                ->assertSessionHasErrors('expected_record_revision');
        }
        $this->assertSame('Newer', $package->fresh()->remarks);
        $this->assertNull($package->fresh()->parcel_id);
        $this->assertNull($package->fresh()->landowner_id);
        $this->assertSame(1, Landowner::count());
        $this->assertSame(1, Parcel::count());
        $this->assertSame(['source-record-packages/current.pdf'], Storage::disk('local')->allFiles('source-record-packages'));
    }

    public function test_package_update_cannot_clear_included_reference_and_child_link_is_managed_by_package(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $package = $this->package($staff);
        $record = LegacyRecord::create([
            'source_record_package_id' => $package->id,
            'record_type' => LegacyRecord::TYPE_TITLE,
            'title_number' => 'SOURCE-TITLE-1',
            'origin' => 'encoded',
            'source_record_scope' => 'reference_only',
        ]);
        $payload = $this->packagePayload($package);
        $this->actingAs($staff)->patch(route('staff.source-record-packages.update', $package), $payload)
            ->assertSessionHasErrors('title_number');
        $this->assertSame('SOURCE-TITLE-1', $record->fresh()->title_number);
        $parcel = Parcel::create(['parcel_code' => 'CHILD-LINK', 'status' => 'active', 'area_hectares' => 1]);
        $this->post(route('staff.legacy-records.link-parcel', $record), [
            'parcel_id' => $parcel->id, 'expected_record_revision' => $record->fresh()->record_revision,
        ])->assertSessionHasErrors('source_record_package');
        $this->assertNull($record->fresh()->parcel_id);
        $this->post(route('staff.source-record-packages.link-parcel', $package), [
            'parcel_id' => $parcel->id, 'expected_record_revision' => $package->fresh()->record_revision,
        ])->assertSessionHasNoErrors();
        $this->assertSame($parcel->id, $record->fresh()->parcel_id);
    }

    public function test_stale_individual_source_create_cannot_leave_extra_parcel(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $record = LegacyRecord::create([
            'record_type' => LegacyRecord::TYPE_TITLE, 'title_number' => 'INDIVIDUAL-TITLE',
            'origin' => 'encoded', 'source_record_scope' => 'reference_only',
        ]);
        $revision = $record->fresh()->record_revision;
        $parcel = Parcel::create(['parcel_code' => 'EXISTING-LINK', 'status' => 'active', 'area_hectares' => 1]);
        $record->update(['parcel_id' => $parcel->id]);
        $this->actingAs($staff)->post(route('staff.legacy-records.create-parcel', $record), [
            'expected_record_revision' => $revision, 'parcel_code' => 'EXTRA-PARCEL', 'status' => 'active',
        ])->assertSessionHasErrors('expected_record_revision');
        $this->assertSame(1, Parcel::count());
        $this->assertSame($parcel->id, $record->fresh()->parcel_id);
    }

    public function test_source_file_replacement_rolls_back_when_audit_fails(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $staff = User::factory()->create(['role' => 'staff']);
        $package = $this->package($staff);
        $path = 'source-record-packages/original.pdf';
        Storage::disk('local')->put($path, 'original');
        $package->update(['source_file_path' => $path]);
        $event = 'eloquent.creating: '.AuditLog::class;
        Event::listen($event, fn () => throw new RuntimeException('Source audit failure.'));
        try {
            $this->withoutExceptionHandling()->actingAs($staff)->post(route('staff.source-record-packages.source-file.store', $package), [
                'expected_record_revision' => $package->fresh()->record_revision,
                'source_file' => UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf'),
            ]);
            $this->fail('Expected audit failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Source audit failure.', $exception->getMessage());
        } finally {
            Event::forget($event);
        }
        $this->assertSame($path, $package->fresh()->source_file_path);
        $this->assertSame([$path], Storage::disk('local')->allFiles('source-record-packages'));
    }

    public function test_package_archive_is_paginated_and_older_packages_are_reachable(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        for ($i = 0; $i < 61; $i++) {
            $this->package($staff, 'ARCHIVE-'.$i);
        }
        $response = $this->actingAs($staff)->get(route('staff.legacy-records.index', ['view' => 'packages']));
        $response->assertOk()->assertViewHas('sourcePackages', fn ($pages) => $pages->total() === 61 && $pages->count() === 15);
        $this->get(route('staff.legacy-records.index', ['view' => 'packages', 'packages_page' => 5]))
            ->assertOk()->assertViewHas('sourcePackages', fn ($pages) => $pages->count() === 1);
    }

    private function documentContext(): array
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = LandTransferApplication::create([
            'application_code' => 'SOURCE-DOC-AUDIT', 'transferor_name' => 'Transferor',
            'transferee_name' => 'Transferee', 'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);
        $parcel = Parcel::create(['parcel_code' => 'DOC-SUBJECT', 'status' => 'active', 'area_hectares' => 1]);
        $application->applicationParcels()->create(['parcel_id' => $parcel->id, 'area_hectares' => 1]);
        $requirement = RequiredDocument::forceCreate([
            'name' => 'Source audit evidence', 'applies_to' => 'transferor', 'is_mandatory' => true,
        ]);
        return [$staff, $application->fresh(), $requirement];
    }

    private function package(User $staff, string $code = 'SOURCE-AUDIT-PACKAGE'): SourceRecordPackage
    {
        return SourceRecordPackage::create([
            'package_code' => $code, 'status' => SourceRecordPackage::STATUS_ENCODED,
            'source_record_scope' => 'reference_only', 'encoded_by_user_id' => $staff->id,
            'landowner_name' => 'Source Owner', 'source_book' => 'Audit book',
            'transcribed_by' => $staff->name, 'transcription_date' => now()->toDateString(),
        ]);
    }

    private function packagePayload(SourceRecordPackage $package): array
    {
        return [
            'expected_record_revision' => $package->fresh()->record_revision,
            'source_record_scope' => 'reference_only', 'landowner_name' => 'Source Owner',
            'source_book' => 'Audit book', 'transcribed_by' => 'Staff', 'transcription_date' => now()->toDateString(),
        ];
    }
}
