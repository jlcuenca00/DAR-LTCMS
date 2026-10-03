<?php

namespace Tests\Feature;

use App\Http\Middleware\LockApplicationMutation;
use App\Models\ApplicationClearance;
use App\Models\ApplicationComplianceNotice;
use App\Models\ApplicationDocument;
use App\Models\ApplicationParcel;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\RequiredDocument;
use App\Models\User;
use App\Services\ApplicationClearanceIntegrityService;
use App\Services\ApplicationClearanceService;
use App\Services\ApplicationComplianceNoticeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinalDecisionIntegrityHardeningTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\WritesWorkflowFixtures;
    use \Tests\Concerns\InsertsClearanceFixtures;

    public function test_application_mutation_middleware_refreshes_and_row_locks_the_route_model(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $application = $this->makeApplication($staff, LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW);
        $staleApplication = $application->fresh();

        LandTransferApplication::query()
            ->whereKey($application->id)
            ->update(['status' => LandTransferApplication::STATUS_DENIED]);

        $request = Request::create(
            "/staff/applications/{$application->id}/form-4-review",
            'PATCH'
        );
        $request->setUserResolver(fn () => $staff);

        $route = new Route(
            ['PATCH'],
            '/staff/applications/{application}/form-4-review',
            fn () => response('ok')
        );
        $route->name('staff.applications.form4.update');
        $route->bind($request);
        $route->setParameter('application', $staleApplication);
        $request->setRouteResolver(fn () => $route);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = strtolower($query->sql);
        });

        $response = app(LockApplicationMutation::class)->handle(
            $request,
            function (Request $request) {
                $this->assertGreaterThan(0, DB::transactionLevel());
                $this->assertSame(
                    LandTransferApplication::STATUS_DENIED,
                    $request->route('application')->status
                );

                return response('ok');
            }
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue(
            collect($queries)->contains(fn ($sql) => str_contains($sql, 'for update')),
            'Expected the application mutation middleware to issue a SELECT ... FOR UPDATE query.'
        );
    }

    public function test_legacy_final_records_cannot_enter_current_release_tracking(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        foreach ([
            LandTransferApplication::STATUS_RELEASED,
            LandTransferApplication::STATUS_DENIED,
        ] as $index => $status) {
            $application = $this->makeApplication(
                $staff,
                $status,
                'LEGACY-RELEASE-' . $index
            );

            // Simulate a pre-existing legacy anomaly without weakening the
            // model-level historical-record freeze.
            $this->writeWorkflowFixture(fn () => DB::table('land_transfer_applications')
                ->where('id', $application->id)
                ->update(['release_status' => LandTransferApplication::RELEASE_READY]));
            $application->refresh();

            $this->assertFalse($application->isReleaseReady());

            $this->actingAs($staff)
                ->post(route('staff.applications.ready_for_release', $application), [
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
            ])
                ->assertSessionHasErrors('status');

            $this->actingAs($staff)
                ->post(route('staff.applications.release', $application), [
                    'expected_workflow_revision' => $application->fresh()->workflow_revision,
                    'release_confirmation' => '1',
                    'release_recipient_name' => 'Legacy Recipient',
                ])
                ->assertSessionHasErrors('status');
        }
    }

    public function test_negative_final_statuses_are_historical_only_and_cannot_be_created_by_current_workflow(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = $this->makeApplication(
            $staff,
            LandTransferApplication::STATUS_FOR_RELEASING,
            'NO-CURRENT-NEGATIVE-DECISION'
        );

        $this->actingAs($staff)
            ->post('/staff/applications/' . $application->id . '/not-approved', [
                'final_decision_confirmation' => '1',
            ])
            ->assertNotFound();

        foreach ([
            LandTransferApplication::STATUS_NOT_APPROVED,
            LandTransferApplication::STATUS_DENIED,
        ] as $historicalStatus) {
            $candidate = $application->fresh();
            $candidate->status = $historicalStatus;

            try {
                $candidate->save();
                $this->fail("Expected {$historicalStatus} to be rejected as historical-only.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('status', $e->errors());
            }

            $this->assertSame(
                LandTransferApplication::STATUS_FOR_RELEASING,
                $application->fresh()->status
            );
        }

        $this->assertDatabaseMissing('application_clearances', [
            'land_transfer_application_id' => $application->id,
        ]);
    }

    public function test_direct_model_status_changes_cannot_bypass_the_guarded_workflow_service(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $advanceCandidate = $this->makeApplication(
            $staff,
            LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'DIRECT-STATUS-ADVANCE-BLOCKED'
        );
        $advanceCandidate->status = LandTransferApplication::STATUS_AWAITING_PAYMENT;

        try {
            $advanceCandidate->save();
            $this->fail('Expected direct workflow advancement to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $this->assertSame(
            LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            $advanceCandidate->fresh()->status
        );

        $approvalCandidate = $this->makeApplication(
            $staff,
            LandTransferApplication::STATUS_FOR_RELEASING,
            'DIRECT-STATUS-APPROVAL-BLOCKED'
        );
        $approvalCandidate->status = LandTransferApplication::STATUS_APPROVED;

        try {
            $approvalCandidate->save();
            $this->fail('Expected direct Approved status mutation to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $this->assertSame(
            LandTransferApplication::STATUS_FOR_RELEASING,
            $approvalCandidate->fresh()->status
        );
        $this->assertDatabaseMissing('application_clearances', [
            'land_transfer_application_id' => $approvalCandidate->id,
        ]);
    }

    public function test_approved_application_model_freezes_core_fields_and_requires_guarded_release_service(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = $this->makeApplication(
            $staff,
            LandTransferApplication::STATUS_APPROVED,
            'MODEL-FREEZE-APPROVED'
        );

        $tampered = $application->fresh();
        $tampered->remarks = 'This core field must not change after approval.';

        try {
            $tampered->save();
            $this->fail('Expected approved application core-field mutation to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('application', $e->errors());
        }

        $this->assertNull($application->fresh()->remarks);

        $directReady = $application->fresh();
        $directReady->forceFill([
            'release_status' => LandTransferApplication::RELEASE_READY,
            'ready_for_release_at' => now(),
        ]);

        try {
            $directReady->save();
            $this->fail('Expected direct Ready for Release mutation to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('release', $e->errors());
        }

        $directReleased = $application->fresh();
        $directReleased->forceFill([
            'release_status' => LandTransferApplication::RELEASED_TO_CLIENT,
            'ready_for_release_at' => now(),
            'released_at' => now(),
            'released_by' => $staff->id,
            'release_recipient_name' => 'Skipped Recipient',
            'date_of_clearance_release' => now()->toDateString(),
        ]);

        try {
            $directReleased->save();
            $this->fail('Expected direct Released to Client mutation to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('release', $e->errors());
        }

        $application = $application->fresh();
        $this->assertSame(
            LandTransferApplication::RELEASE_NOT_READY,
            $application->release_status ?: LandTransferApplication::RELEASE_NOT_READY
        );
    }

    public function test_workflow_owned_evidence_fields_reject_direct_model_mutation(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $payment = $this->makeApplication(
            $staff,
            LandTransferApplication::STATUS_AWAITING_PAYMENT,
            'DIRECT-EVIDENCE-PAYMENT-BLOCKED'
        );
        $payment->or_number = 'OR-BYPASS-001';

        try {
            $payment->save();
            $this->fail('Expected direct payment evidence mutation to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('workflow', $e->errors());
        }

        $decision = $this->makeApplication(
            $staff,
            LandTransferApplication::STATUS_FOR_RELEASING,
            'DIRECT-EVIDENCE-DECISION-BLOCKED'
        );
        $decision->decision_officer_name = 'Bypass Officer';

        try {
            $decision->save();
            $this->fail('Expected direct final-decision evidence mutation to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('workflow', $e->errors());
        }
    }

    public function test_historical_final_application_records_reject_direct_model_edits(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        foreach (LandTransferApplication::LEGACY_FINAL_STATUSES as $index => $status) {
            $application = $this->makeApplication(
                $staff,
                $status,
                'MODEL-FREEZE-HISTORICAL-' . $index
            );

            $application->remarks = 'Historical record tamper attempt.';

            try {
                $application->save();
                $this->fail("Expected historical final status {$status} to reject model edits.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('application', $e->errors());
            }

            $this->assertNull($application->fresh()->remarks);
        }
    }

    public function test_finalized_application_child_parcels_are_model_immutable_without_parent_share_bypass(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $parcel = $this->makeParcel('FINAL-CHILD-PARCEL-001');
        $otherParcel = $this->makeParcel('FINAL-CHILD-PARCEL-002');

        $application = LandTransferApplication::create([
            'application_code' => 'FINAL-CHILD-PARCELS-001',
            'transferor_name' => 'Final Parcel Transferor',
            'transferors' => [[
                'name' => 'Final Parcel Transferor',
                'landowner_id' => null,
                'parcel_shares' => [],
            ]],
            'transferee_name' => 'Final Parcel Transferee',
            'transferees' => [[
                'name' => 'Final Parcel Transferee',
                'landowner_id' => null,
                'parcel_shares' => [],
            ]],
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        $applicationParcel = ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'title_no' => $parcel->title_no,
            'lot_number' => $parcel->lot_number,
            'area_hectares' => 1.0000,
        ]);

        $application->transferees = [[
            'name' => 'Final Parcel Transferee',
            'landowner_id' => null,
            'parcel_shares' => [(string) $applicationParcel->id => 1.0000],
        ]];
        $application->save();

        $this->writeWorkflowFixture(fn () => DB::table('land_transfer_applications')
            ->where('id', $application->id)
            ->update(['status' => LandTransferApplication::STATUS_APPROVED]));
        $application->refresh();

        $originalTransferees = $application->transferees;

        $applicationParcel->area_hectares = 2.0000;
        try {
            $applicationParcel->save();
            $this->fail('Expected finalized ApplicationParcel update to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('application_parcel', $e->errors());
        }

        try {
            ApplicationParcel::create([
                'land_transfer_application_id' => $application->id,
                'parcel_id' => $otherParcel->id,
                'parcel_code' => $otherParcel->parcel_code,
                'title_no' => $otherParcel->title_no,
                'lot_number' => $otherParcel->lot_number,
                'area_hectares' => 1.0000,
            ]);
            $this->fail('Expected finalized ApplicationParcel creation to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('application_parcel', $e->errors());
        }

        try {
            $applicationParcel->fresh()->delete();
            $this->fail('Expected finalized ApplicationParcel deletion to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('application_parcel', $e->errors());
        }

        $this->assertDatabaseHas('application_parcels', ['id' => $applicationParcel->id]);
        $this->assertSame($originalTransferees, $application->fresh()->transferees);
    }

    public function test_finalized_application_documents_are_model_immutable(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = $this->makeApplication(
            $staff,
            LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'FINAL-CHILD-DOCUMENTS'
        );

        $required = RequiredDocument::forceCreate([
            'name' => 'Final Child Document',
            'applies_to' => 'transferor',
            'is_mandatory' => true,
            'legal_basis' => 'Regression test',
        ]);
        $secondRequired = RequiredDocument::forceCreate([
            'name' => 'Final Child Document Two',
            'applies_to' => 'transferor',
            'is_mandatory' => false,
            'legal_basis' => 'Regression test',
        ]);

        $document = ApplicationDocument::create([
            'land_transfer_application_id' => $application->id,
            'required_document_id' => $required->id,
            'file_path' => 'application-documents/final-child.pdf',
            'original_filename' => 'final-child.pdf',
            'uploaded_by' => $staff->id,
            'remarks' => 'Original evidence',
        ]);

        $this->writeWorkflowFixture(fn () => DB::table('land_transfer_applications')
            ->where('id', $application->id)
            ->update(['status' => LandTransferApplication::STATUS_APPROVED]));
        $application->refresh();

        $document->remarks = 'Tampered evidence';
        try {
            $document->save();
            $this->fail('Expected finalized ApplicationDocument update to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('document', $e->errors());
        }

        try {
            ApplicationDocument::create([
                'land_transfer_application_id' => $application->id,
                'required_document_id' => $secondRequired->id,
                'file_path' => 'application-documents/final-child-two.pdf',
                'original_filename' => 'final-child-two.pdf',
                'uploaded_by' => $staff->id,
            ]);
            $this->fail('Expected finalized ApplicationDocument creation to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('document', $e->errors());
        }

        try {
            $document->fresh()->delete();
            $this->fail('Expected finalized ApplicationDocument deletion to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('document', $e->errors());
        }

        $this->assertSame('Original evidence', $document->fresh()->remarks);
        $this->assertDatabaseHas('application_documents', ['id' => $document->id]);
    }

    public function test_compliance_notices_are_service_created_resolve_once_and_then_immutable(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = $this->makeApplication(
            $staff,
            LandTransferApplication::STATUS_LEGAL_EVALUATION,
            'COMPLIANCE-HISTORY-GUARD'
        );

        try {
            ApplicationComplianceNotice::create([
                'land_transfer_application_id' => $application->id,
                'category' => ApplicationComplianceNotice::CATEGORY_CLARIFICATION,
                'details' => 'Direct creation must fail.',
                'resume_status' => LandTransferApplication::STATUS_LEGAL_EVALUATION,
                'requested_by' => $staff->id,
                'requested_by_name_snapshot' => $staff->name,
                'requested_at' => now(),
            ]);
            $this->fail('Expected direct compliance notice creation to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('compliance', $e->errors());
        }

        $service = app(ApplicationComplianceNoticeService::class);
        $notice = $service->create($application, [
            'category' => ApplicationComplianceNotice::CATEGORY_CLARIFICATION,
            'details' => 'Original compliance reason.',
            'requested_items' => 'Original requested item.',
            'resume_status' => LandTransferApplication::STATUS_LEGAL_EVALUATION,
            'requested_by' => $staff->id,
            'requested_by_name_snapshot' => $staff->name,
            'requested_at' => now(),
        ]);

        $notice = $service->resolve($notice, [
            'resolved_by' => $staff->id,
            'resolved_by_name_snapshot' => $staff->name,
            'resolved_at' => now(),
            'resolution_note' => 'Resolved once.',
        ]);

        $notice->details = 'Tampered compliance reason.';
        try {
            $notice->save();
            $this->fail('Expected resolved compliance notice mutation to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('compliance', $e->errors());
        }

        try {
            $notice->fresh()->delete();
            $this->fail('Expected persisted compliance notice deletion to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('compliance', $e->errors());
        }

        $this->assertSame('Original compliance reason.', $notice->fresh()->details);
        $this->assertSame('Resolved once.', $notice->fresh()->resolution_note);

        $this->writeWorkflowFixture(fn () => DB::table('land_transfer_applications')
            ->where('id', $application->id)
            ->update(['status' => LandTransferApplication::STATUS_APPROVED]));
        $application->refresh();

        try {
            $service->create($application, [
                'category' => ApplicationComplianceNotice::CATEGORY_OTHER,
                'other_category' => 'Late compliance',
                'details' => 'Finalized parent must block this.',
                'resume_status' => LandTransferApplication::STATUS_LEGAL_EVALUATION,
                'requested_by' => $staff->id,
                'requested_by_name_snapshot' => $staff->name,
                'requested_at' => now(),
            ]);
            $this->fail('Expected finalized parent to reject new compliance history.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('compliance', $e->errors());
        }
    }

    public function test_clearance_integrity_checks_versioned_final_decision_identity_fields(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = LandTransferApplication::create([
            'application_code' => 'INTEGRITY-DECISION-IDENTITY-001',
            'transferor_name' => 'Integrity Transferor',
            'transferee_name' => 'Integrity Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_APPROVED,
            'encoded_by' => $staff->id,
            'reviewed_by' => $staff->id,
            'reviewed_at' => '2026-10-01 10:00:00',
            'validated_at' => '2026-10-01 10:00:00',
            'validation_snapshot' => ['workflow_readiness' => ['complete' => true]],
            'decision_authority' => LandTransferApplication::FINAL_DECISION_AUTHORITY,
            'decision_officer_name' => 'PARPO II Correct Signatory',
            'decision_date' => '2026-10-01',
            'decision_recorded_by' => $staff->id,
            'decision_recorded_at' => '2026-10-01 10:00:00',
        ]);

        $this->insertClearanceFixture([
            'land_transfer_application_id' => $application->id,
            'clearance_number' => '1803-2026-9801 (1)',
            'decision_status' => LandTransferApplication::STATUS_APPROVED,
            'decision_authority' => LandTransferApplication::FINAL_DECISION_AUTHORITY,
            'decision_officer_name' => 'PARPO II Wrong Signatory',
            'decision_date' => '2026-10-01',
            'decision_recorded_by' => $staff->id,
            'decision_recorded_at' => '2026-10-01 10:00:00',
            'application_code' => $application->application_code,
            'transferor_name' => $application->transferorDisplayName(),
            'transferee_name' => $application->transfereeDisplayName(),
            'municipality' => $application->municipality,
            'barangay' => $application->barangay,
            'total_area_hectares' => '0.0000',
            'parcel_snapshot' => [],
            'form_snapshot' => ['snapshot_version' => 1],
            'review_officer_name' => 'PARPO II Wrong Signatory',
            'reviewed_at' => '2026-10-01 10:00:00',
            'generated_by' => $staff->id,
            'generated_at' => '2026-10-01 10:00:00',
        ]);

        $inspection = app(ApplicationClearanceIntegrityService::class)
            ->inspect($application->fresh());

        $this->assertFalse($inspection['valid']);
        $this->assertContains(
            'Clearance decision_officer_name does not match the frozen application final-decision record.',
            $inspection['issues']
        );

        $this->actingAs($staff)
            ->post(route('staff.applications.ready_for_release', $application), [
                'expected_workflow_revision' => $application->fresh()->workflow_revision,
            ])
            ->assertSessionHasErrors('clearance');

        $this->assertSame(
            LandTransferApplication::RELEASE_NOT_READY,
            $application->fresh()->release_status
        );
    }

    public function test_all_final_statuses_reject_linked_parcel_additions(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $parcel = $this->makeParcel('FINAL-LOCK-PARCEL-ADD');

        foreach ($this->finalStatuses() as $index => $status) {
            $application = $this->makeApplication($staff, $status, 'FINAL-PARCEL-' . $index);

            $this->actingAs($staff)
                ->post(route('staff.applications.parcels.store', $application), [
                    'parcel_id' => $parcel->id,
                    'area_hectares' => 1.0000,
                ])
                ->assertSessionHas('error', 'Linked parcel records are locked after final decision.');

            $this->assertDatabaseMissing('application_parcels', [
                'land_transfer_application_id' => $application->id,
                'parcel_id' => $parcel->id,
            ]);
        }
    }

    public function test_finalized_application_rejects_parcel_removal_landowner_link_form4_and_metadata_mutations(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $transferor = $this->makeLandowner('Locked', 'Transferor');
        $otherTransferor = $this->makeLandowner('Other', 'Transferor');
        $transferee = $this->makeLandowner('Locked', 'Transferee');
        $parcel = $this->makeParcel('FINAL-LOCK-PARCEL-EXISTING');

        $application = LandTransferApplication::create([
            'application_code' => 'FINAL-INTEGRITY-001',
            'transferor_name' => $transferor->full_name,
            'transferors' => [[
                'name' => $transferor->full_name,
                'landowner_id' => $transferor->id,
                'parcel_shares' => [],
            ]],
            'transferee_name' => $transferee->full_name,
            'transferees' => [[
                'name' => $transferee->full_name,
                'landowner_id' => $transferee->id,
                'parcel_shares' => [],
            ]],
            'transferor_landowner_id' => $transferor->id,
            'transferee_landowner_id' => $transferee->id,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_RELEASED,
            'encoded_by' => $staff->id,
        ]);

        $applicationParcelId = $this->writeWorkflowFixture(fn () => DB::table('application_parcels')->insertGetId([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'title_no' => $parcel->title_no,
            'lot_number' => $parcel->lot_number,
            'area_hectares' => 1.0000,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        $applicationParcel = ApplicationParcel::findOrFail($applicationParcelId);

        $this->actingAs($staff)
            ->delete(route('staff.applications.parcels.destroy', [$application, $applicationParcel]))
            ->assertSessionHas('error', 'Linked parcel records are locked after final decision.');

        $this->assertDatabaseHas('application_parcels', ['id' => $applicationParcel->id]);

        $this->actingAs($staff)
            ->patch(route('staff.applications.landowner-links.update', $application), [
                'expected_workflow_revision' => (int) $application->fresh()->workflow_revision,
                'expected_party_dependency' => app(\App\Services\ApplicationPartyLinkReviewService::class)->fingerprint($application->fresh()),
                'transferors' => [[
                    'name' => $transferor->full_name,
                    'landowner_id' => $otherTransferor->id,
                    'parcel_shares' => [],
                ]],
                'transferees' => [[
                    'name' => $transferee->full_name,
                    'landowner_id' => $transferee->id,
                    'parcel_shares' => [],
                ]],
            ])
            ->assertSessionHas('error', 'Finalized applications are locked. Landowner record links can no longer be changed.');

        $application->refresh();
        $this->assertSame($transferor->id, (int) $application->partyRows('transferor')[0]['landowner_id']);

        $this->actingAs($staff)
            ->patch(route('staff.applications.form4.update', $application), [
                    'expected_workflow_revision' => $application->fresh()->workflow_revision,
                'ltc_form4_other_findings' => 'This must never be written after final decision.',
                'ltc_form4_recommendation_decision' => 'approval',
            ])
            ->assertSessionHas('error', 'LTC Form No. 4 review details are locked after a final clearance decision record.');

        $application->refresh();
        $this->assertNull($application->ltc_form4_other_findings);
        $this->assertNull($application->ltc_form4_recommendation_decision);

        $requiredDocument = RequiredDocument::forceCreate([
            'name' => 'Finalized Metadata Requirement',
            'applies_to' => 'transferor',
            'is_mandatory' => true,
            'legal_basis' => 'Integrity regression test',
        ]);

        $this->actingAs($staff)
            ->post(route('staff.applications.documents.store', [$application, $requiredDocument]), [ 'expected_workflow_revision' => $application->fresh()->workflow_revision, 
                'annex_reference' => 'Should not save',
                'document_metadata' => [
                    'title_number' => 'SHOULD-NOT-SAVE',
                ],
            ])
            ->assertSessionHas('error', 'This application is already finalized. Document uploads and metadata encoding are locked.');

        $this->assertDatabaseMissing('application_documents', [
            'land_transfer_application_id' => $application->id,
            'required_document_id' => $requiredDocument->id,
        ]);
    }

    public function test_denied_application_rejects_creating_and_linking_a_landowner_from_a_party(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $application = LandTransferApplication::create([
            'application_code' => 'FINAL-LANDOWNER-CREATE-001',
            'transferor_name' => 'Unlinked Transferor',
            'transferors' => [[
                'name' => 'Unlinked Transferor',
                'landowner_id' => null,
                'parcel_shares' => [],
            ]],
            'transferee_name' => 'Unlinked Transferee',
            'transferees' => [[
                'name' => 'Unlinked Transferee',
                'landowner_id' => null,
                'parcel_shares' => [],
            ]],
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
            'encoded_by' => $staff->id,
        ]);

        $this->writeWorkflowFixture(fn () => DB::table('land_transfer_applications')
            ->where('id', $application->id)
            ->update(['status' => LandTransferApplication::STATUS_DENIED]));
        $application->refresh();

        $beforeCount = Landowner::count();

        $this->actingAs($staff)
            ->post(route('staff.applications.landowner-records.create', $application), [
                'expected_workflow_revision' => (int) $application->fresh()->workflow_revision,
                'expected_party_dependency' => app(\App\Services\ApplicationPartyLinkReviewService::class)->fingerprint($application->fresh()),
                'party' => 'transferor',
                'index' => 0,
            ])
            ->assertSessionHas('error', 'Finalized applications are locked. New landowner records can no longer be linked from this application.');

        $this->assertSame($beforeCount, Landowner::count());
        $this->assertNull($application->fresh()->partyRows('transferor')[0]['landowner_id']);
    }

    public function test_clearance_generation_is_create_once_and_does_not_rewrite_the_final_snapshot(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $parcel = $this->makeParcel('IMMUTABLE-CLEARANCE-PARCEL');

        $application = LandTransferApplication::create([
            'application_code' => 'IMMUTABLE-CLEARANCE-001',
            'transferor_name' => 'Original Transferor',
            'transferors' => [[
                'name' => 'Original Transferor',
                'landowner_id' => null,
                'parcel_shares' => [],
            ]],
            'transferee_name' => 'Original Transferee',
            'transferees' => [[
                'name' => 'Original Transferee',
                'landowner_id' => null,
                'parcel_shares' => [],
            ]],
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_APPROVED,
            'encoded_by' => $staff->id,
            'reviewed_by' => $staff->id,
            'reviewed_at' => now(),
            'date_of_clearance_release' => now()->toDateString(),
        ]);

        $applicationParcelId = $this->writeWorkflowFixture(fn () => DB::table('application_parcels')->insertGetId([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'title_no' => $parcel->title_no,
            'lot_number' => $parcel->lot_number,
            'area_hectares' => 1.0000,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        $applicationParcel = ApplicationParcel::findOrFail($applicationParcelId);

        $service = app(ApplicationClearanceService::class);
        $first = $service->generateForDecision($application, $staff->id);

        $originalSnapshot = [
            'id' => $first->id,
            'clearance_number' => $first->clearance_number,
            'transferor_name' => $first->transferor_name,
            'transferee_name' => $first->transferee_name,
            'municipality' => $first->municipality,
            'total_area_hectares' => (string) $first->total_area_hectares,
            'parcel_snapshot' => $first->parcel_snapshot,
            'generated_at' => $first->getRawOriginal('generated_at'),
        ];

        // Simulate out-of-band database corruption to prove an existing immutable
        // decision output is never regenerated from altered source records.
        $this->writeWorkflowFixture(fn () => DB::table('land_transfer_applications')
            ->where('id', $application->id)
            ->update(['municipality' => 'Bais City']));
        $this->writeWorkflowFixture(fn () => DB::table('application_parcels')
            ->where('id', $applicationParcel->id)
            ->update(['area_hectares' => 9.9999]));

        $second = $service->generateForDecision($application->fresh(), $staff->id);

        $this->assertSame($originalSnapshot['id'], $second->id);
        $this->assertSame($originalSnapshot['clearance_number'], $second->clearance_number);
        $this->assertSame($originalSnapshot['transferor_name'], $second->transferor_name);
        $this->assertSame($originalSnapshot['transferee_name'], $second->transferee_name);
        $this->assertSame($originalSnapshot['municipality'], $second->municipality);
        $this->assertSame($originalSnapshot['total_area_hectares'], (string) $second->total_area_hectares);
        $this->assertSame($originalSnapshot['parcel_snapshot'], $second->parcel_snapshot);
        $this->assertSame($originalSnapshot['generated_at'], $second->getRawOriginal('generated_at'));
        $this->assertDatabaseCount('application_clearances', 1);
    }

    public function test_final_clearance_snapshot_rejects_model_update_and_delete(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $parcel = $this->makeParcel('IMMUTABLE-MODEL-PARCEL');

        $application = LandTransferApplication::create([
            'application_code' => 'IMMUTABLE-MODEL-001',
            'transferor_name' => 'Immutable Transferor',
            'transferee_name' => 'Immutable Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => LandTransferApplication::STATUS_APPROVED,
            'encoded_by' => $staff->id,
            'reviewed_by' => $staff->id,
            'reviewed_at' => now(),
        ]);

        $this->writeWorkflowFixture(fn () => DB::table('application_parcels')->insert([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'title_no' => $parcel->title_no,
            'lot_number' => $parcel->lot_number,
            'area_hectares' => 1.0000,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $clearance = app(ApplicationClearanceService::class)
            ->generateForDecision($application, $staff->id);

        try {
            $clearance->update(['transferor_name' => 'Tampered']);
            $this->fail('Expected immutable clearance update to be rejected.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('immutable', strtolower($exception->getMessage()));
        }

        $this->assertSame('Immutable Transferor', $clearance->fresh()->transferor_name);

        try {
            $clearance->delete();
            $this->fail('Expected immutable clearance deletion to be rejected.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('immutable', strtolower($exception->getMessage()));
        }

        $this->assertDatabaseHas('application_clearances', ['id' => $clearance->id]);
    }


    public function test_protected_models_reject_quiet_and_globally_suppressed_writes(): void
    {
        foreach ([
            LandTransferApplication::class,
            ApplicationParcel::class,
            ApplicationDocument::class,
            ApplicationComplianceNotice::class,
            ApplicationClearance::class,
        ] as $class) {
            foreach (['saveQuietly', 'deleteQuietly', 'incrementQuietly', 'decrementQuietly'] as $method) {
                $model = new $class;
                try {
                    if (str_contains($method, 'crement')) {
                        $model->{$method}('id');
                    } else {
                        $model->{$method}();
                    }
                    $this->fail("Expected {$class}::{$method} to reject suppressed events.");
                } catch (\LogicException $e) {
                    $this->assertStringContainsString('event-suppressed writes', $e->getMessage());
                }
            }
            foreach (['save', 'delete', 'increment', 'decrement'] as $method) {
                try {
                    // Eloquent's dispatcher is shared even when suppression was
                    // initiated through an unrelated model.
                    User::withoutEvents(function () use ($class, $method) {
                        $model = new $class;
                        str_contains($method, 'crement') ? $model->{$method}('id') : $model->{$method}();
                    });
                    $this->fail("Expected globally suppressed {$class}::{$method} to fail.");
                } catch (\LogicException $e) {
                    $this->assertStringContainsString('event-suppressed writes', $e->getMessage());
                }
            }
            $dispatcher = $class::getEventDispatcher();
            try {
                $class::unsetEventDispatcher();
                try {
                    (new $class)->save();
                    $this->fail('Expected a missing dispatcher to reject the write.');
                } catch (\LogicException $e) {
                    $this->assertStringContainsString('require model events', $e->getMessage());
                }
            } finally {
                $class::setEventDispatcher($dispatcher);
            }
        }
    }

    public function test_quiet_save_cannot_change_final_application_or_its_revision(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $application = $this->makeApplication($staff, LandTransferApplication::STATUS_APPROVED, 'QUIET-FINAL');
        $revision = $application->fresh()->workflow_revision;
        $application->remarks = 'Bypass attempt';

        try {
            $application->saveQuietly();
            $this->fail('Expected quiet finalized mutation to fail.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('event-suppressed writes', $e->getMessage());
        }

        $this->assertNull($application->fresh()->remarks);
        $this->assertSame($revision, $application->fresh()->workflow_revision);
    }

    public function test_persisted_parcel_and_document_links_cannot_be_reassigned(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $source = $this->makeApplication($staff, LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW, 'REPARENT-SOURCE');
        $target = $this->makeApplication($staff, LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW, 'REPARENT-TARGET');
        $parcel = $this->makeParcel('REPARENT-PARCEL');
        $child = ApplicationParcel::create([
            'land_transfer_application_id' => $source->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'area_hectares' => 1,
        ]);
        $requirement = RequiredDocument::create([
            'name' => 'Reparent Fixture',
            'applies_to' => 'transferor',
            'is_mandatory' => false,
        ]);
        $document = ApplicationDocument::create([
            'land_transfer_application_id' => $source->id,
            'required_document_id' => $requirement->id,
            'file_path' => 'fixtures/reparent.pdf',
            'original_filename' => 'reparent.pdf',
            'uploaded_by' => $staff->id,
        ]);

        foreach ([LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW, LandTransferApplication::STATUS_APPROVED] as $status) {
            // Explicit final-state fixture; normal workflow tests exercise approval.
            $this->writeWorkflowFixture(fn () => DB::table('land_transfer_applications')->where('id', $source->id)->update(['status' => $status]));
            $sourceRevision = $source->fresh()->workflow_revision;
            $targetRevision = $target->fresh()->workflow_revision;
            foreach ([$child, $document] as $record) {
                $candidate = $record->fresh();
                $candidate->land_transfer_application_id = $target->id;
                try {
                    $candidate->save();
                    $this->fail('Expected parent reassignment to fail.');
                } catch (ValidationException $e) {
                    $this->assertArrayHasKey('application', $e->errors());
                }
                $this->assertSame($source->id, $record->fresh()->land_transfer_application_id);
            }
            $this->assertSame($sourceRevision, $source->fresh()->workflow_revision);
            $this->assertSame($targetRevision, $target->fresh()->workflow_revision);
        }
    }

    private function finalStatuses(): array
    {
        return [
            LandTransferApplication::STATUS_RELEASED,
            LandTransferApplication::STATUS_DENIED,
            LandTransferApplication::STATUS_APPROVED,
            LandTransferApplication::STATUS_NOT_APPROVED,
        ];
    }

    private function makeApplication(User $staff, string $status, ?string $suffix = null): LandTransferApplication
    {
        $suffix ??= strtoupper(str_replace('_', '-', $status));
        $historicalNegative = in_array($status, [
            LandTransferApplication::STATUS_NOT_APPROVED,
            LandTransferApplication::STATUS_DENIED,
        ], true);

        $application = LandTransferApplication::create([
            'application_code' => 'INTEGRITY-' . $suffix,
            'transferor_name' => 'Integrity Transferor',
            'transferee_name' => 'Integrity Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => $historicalNegative
                ? LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW
                : $status,
            'encoded_by' => $staff->id,
        ]);

        if ($historicalNegative) {
            $this->writeWorkflowFixture(fn () => DB::table('land_transfer_applications')
                ->where('id', $application->id)
                ->update(['status' => $status]));
            $application->refresh();
        }

        return $application;
    }

    private function makeParcel(string $code): Parcel
    {
        return Parcel::create([
            'parcel_code' => $code,
            'title_no' => 'T-' . $code,
            'lot_number' => 'LOT-' . $code,
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'area_hectares' => 10.0000,
            'status' => 'active',
        ]);
    }

    private function makeLandowner(string $firstName, string $lastName): Landowner
    {
        return Landowner::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'province' => 'Negros Oriental',
        ]);
    }
}
