<?php

namespace Tests\Feature;

use App\Http\Middleware\LockApplicationMutation;
use App\Models\ApplicationParcel;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\RequiredDocument;
use App\Models\User;
use App\Services\ApplicationClearanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinalDecisionIntegrityHardeningTest extends TestCase
{
    use RefreshDatabase;

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

        $applicationParcel = ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'title_no' => $parcel->title_no,
            'lot_number' => $parcel->lot_number,
            'area_hectares' => 1.0000,
        ]);

        $this->actingAs($staff)
            ->delete(route('staff.applications.parcels.destroy', [$application, $applicationParcel]))
            ->assertSessionHas('error', 'Linked parcel records are locked after final decision.');

        $this->assertDatabaseHas('application_parcels', ['id' => $applicationParcel->id]);

        $this->actingAs($staff)
            ->patch(route('staff.applications.landowner-links.update', $application), [
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
                'ltc_form4_other_findings' => 'This must never be written after release.',
                'ltc_form4_recommendation_decision' => 'approval',
            ])
            ->assertSessionHas('error', 'LTC Form No. 4 review details are locked after release or denial.');

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
            ->post(route('staff.applications.documents.store', [$application, $requiredDocument]), [
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
            'status' => LandTransferApplication::STATUS_DENIED,
            'encoded_by' => $staff->id,
        ]);

        $beforeCount = Landowner::count();

        $this->actingAs($staff)
            ->post(route('staff.applications.landowner-records.create', $application), [
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
            'status' => LandTransferApplication::STATUS_RELEASED,
            'encoded_by' => $staff->id,
            'reviewed_by' => $staff->id,
            'reviewed_at' => now(),
            'date_of_clearance_release' => now()->toDateString(),
        ]);

        $applicationParcel = ApplicationParcel::create([
            'land_transfer_application_id' => $application->id,
            'parcel_id' => $parcel->id,
            'parcel_code' => $parcel->parcel_code,
            'title_no' => $parcel->title_no,
            'lot_number' => $parcel->lot_number,
            'area_hectares' => 1.0000,
        ]);

        $service = app(ApplicationClearanceService::class);
        $first = $service->generateForDecision($application, $staff->id);

        $originalSnapshot = [
            'id' => $first->id,
            'clearance_number' => $first->clearance_number,
            'transferor_name' => $first->transferor_name,
            'transferee_name' => $first->transferee_name,
            'total_area_hectares' => (string) $first->total_area_hectares,
            'parcel_snapshot' => $first->parcel_snapshot,
            'generated_at' => $first->getRawOriginal('generated_at'),
        ];

        // Deliberately alter source records directly to prove regeneration cannot
        // rewrite the preserved final output snapshot.
        $application->forceFill([
            'transferor_name' => 'Changed Source Transferor',
            'transferors' => [[
                'name' => 'Changed Source Transferor',
                'landowner_id' => null,
                'parcel_shares' => [],
            ]],
        ])->save();
        $applicationParcel->update(['area_hectares' => 9.9999]);

        $second = $service->generateForDecision($application->fresh(), $staff->id);

        $this->assertSame($originalSnapshot['id'], $second->id);
        $this->assertSame($originalSnapshot['clearance_number'], $second->clearance_number);
        $this->assertSame($originalSnapshot['transferor_name'], $second->transferor_name);
        $this->assertSame($originalSnapshot['transferee_name'], $second->transferee_name);
        $this->assertSame($originalSnapshot['total_area_hectares'], (string) $second->total_area_hectares);
        $this->assertSame($originalSnapshot['parcel_snapshot'], $second->parcel_snapshot);
        $this->assertSame($originalSnapshot['generated_at'], $second->getRawOriginal('generated_at'));
        $this->assertDatabaseCount('application_clearances', 1);
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

        return LandTransferApplication::create([
            'application_code' => 'INTEGRITY-' . $suffix,
            'transferor_name' => 'Integrity Transferor',
            'transferee_name' => 'Integrity Transferee',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'status' => $status,
            'encoded_by' => $staff->id,
        ]);
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
