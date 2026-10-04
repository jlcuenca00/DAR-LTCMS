<?php

namespace Tests\Feature;

use App\Models\ApplicationComplianceNotice;
use App\Models\ApplicationDocument;
use App\Models\ApplicationParcel;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\RequiredDocument;
use App\Models\User;
use App\Services\ApplicationRequirementService;
use App\Services\DashboardRequirementAttentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardScalingTest extends TestCase
{
    use RefreshDatabase;

    public function test_attention_counts_and_oldest_previews_match_intake_rules_across_multiple_batches(): void
    {
        $this->freezeTime();
        $staff = User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true]);
        $requirement = RequiredDocument::create([
            'name' => 'Current City Evidence', 'applies_to' => 'transferor',
            'requirement_classification' => RequiredDocument::CLASSIFICATION_CASE_DEPENDENT,
            'condition_key' => RequiredDocument::CONDITION_CITY_JURISDICTION,
            'blocks_acceptance' => true, 'max_age_months' => 3,
        ]);
        $parcel = Parcel::create([
            'parcel_code' => 'SCALE-PARCEL', 'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan', 'province' => 'Negros Oriental',
            'area_hectares' => 1, 'status' => 'active', 'title_type' => 'untitled',
        ]);
        foreach (range(1, 224) as $i) {
            $application = LandTransferApplication::forceCreate([
                'application_code' => 'APP-SCALE-' . $i,
                'transferor_name' => 'Transferor', 'transferee_name' => 'Transferee',
                'municipality' => $i % 4 === 0 ? 'Valencia' : 'Dumaguete City',
                'status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
                'date_of_application' => now()->toDateString(), 'encoded_by' => $staff->id,
                // The last batch contains the oldest updates, including ties.
                'created_at' => now(), 'updated_at' => now()->subDays(intdiv($i, 2)),
            ]);
            if ($i % 7 !== 0) {
                ApplicationParcel::create([
                    'land_transfer_application_id' => $application->id,
                    'parcel_id' => $parcel->id, 'parcel_code' => $parcel->parcel_code,
                    'area_hectares' => 1,
                ]);
            }
            if ($i % 3 !== 0) {
                ApplicationDocument::create([
                    'land_transfer_application_id' => $application->id,
                    'required_document_id' => $requirement->id, 'uploaded_by' => $staff->id,
                    'original_filename' => 'metadata-only',
                    'document_metadata' => ['date_issued' => now()->subMonths($i % 2 === 0 ? 1 : 6)->toDateString()],
                ]);
            }
            $application->timestamps = false;
            $application->updated_at = now()->subDays(intdiv($i, 2));
            $application->save();
        }

        $statuses = [LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW];
        $evaluator = app(ApplicationRequirementService::class);
        $all = LandTransferApplication::query()->with(['documents.requiredDocument', 'applicationParcels.parcel'])
            ->oldest('updated_at')->orderBy('id')->get();
        $groups = $all->groupBy(fn ($application) => $evaluator->evaluate($application)['complete']
            ? 'requirements_complete' : 'missing_requirements');

        DB::enableQueryLog();
        $summary = app(DashboardRequirementAttentionService::class)->summarize($statuses);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        foreach (['missing_requirements', 'requirements_complete'] as $key) {
            $expected = $groups->get($key, collect());
            $this->assertGreaterThan(12, $expected->count());
            $this->assertSame($expected->count(), $summary['counts'][$key]);
            $this->assertSame($expected->take(12)->pluck('id')->all(), $summary['preview_ids'][$key]);
        }
        $parcelQueries = collect($queries)->filter(fn ($query) => str_contains($query['query'], 'from "parcels"'));
        $this->assertCount(3, $parcelQueries);
        foreach ($parcelQueries as $query) {
            $this->assertStringNotContainsString('geometry_geojson', $query['query']);
            $this->assertStringNotContainsString('select *', $query['query']);
        }

        $this->actingAs($staff)->get(route('staff.dashboard', ['attention' => 'requirements_complete']))
            ->assertOk()->assertViewHas('actionApplications', fn ($rows) =>
                $rows->pluck('id')->all() === $summary['preview_ids']['requirements_complete']);

        // No stale cross-request cache: changing evidence immediately changes the summary.
        $changed = $groups['requirements_complete']->firstWhere('municipality', 'Dumaguete City');
        $changed->documents()->delete();
        $after = app(DashboardRequirementAttentionService::class)->summarize($statuses);
        $this->assertSame($summary['counts']['requirements_complete'] - 1, $after['counts']['requirements_complete']);
    }

    public function test_attention_summary_handles_no_active_records(): void
    {
        $summary = app(DashboardRequirementAttentionService::class)
            ->summarize([LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW]);
        $this->assertSame(['missing_requirements' => 0, 'requirements_complete' => 0], $summary['counts']);
        $this->assertSame(['missing_requirements' => [], 'requirements_complete' => []], $summary['preview_ids']);
    }

    public function test_landowner_compliance_alerts_paginate_all_linked_records_and_keep_private_records_out(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true]);
        $user = User::factory()->create(['role' => User::ROLE_LANDOWNER, 'is_active' => true]);
        $owner = Landowner::create([
            'user_id' => $user->id, 'first_name' => 'Scale', 'last_name' => 'Owner', 'province' => 'Negros Oriental',
        ]);
        foreach (range(1, 9) as $i) {
            $application = LandTransferApplication::create([
                'application_code' => 'APP-COMPLIANCE-SCALE-' . $i,
                'transferor_name' => 'Scale Owner', 'transferee_name' => 'Transferee',
                'transferor_landowner_id' => $i === 9 ? null : $owner->id,
                'status' => LandTransferApplication::STATUS_RETURNED_FOR_COMPLIANCE,
                'returned_for_compliance_at' => now(), 'encoded_by' => $staff->id,
            ]);
            $notice = new ApplicationComplianceNotice([
                'land_transfer_application_id' => $application->id,
                'category' => ApplicationComplianceNotice::CATEGORY_MISSING_REQUIREMENT,
                'details' => 'Required evidence ' . $i,
                'resume_status' => LandTransferApplication::STATUS_PENDING_LEGAL_REVIEW,
                'requested_by' => $staff->id, 'requested_by_name_snapshot' => $staff->name,
                'requested_at' => now(),
            ]);
            $notice->runAuthorizedCreation(fn () => $notice->save());
        }

        $first = $this->actingAs($user)->get(route('landowner.dashboard'));
        $first->assertOk()->assertSee('Showing 1–5 of 8')->assertDontSee('Required evidence 9')
            ->assertSee('Compliance alert pages')->assertSee('compliance_page=2', false);
        $paginator = $first->viewData('complianceApplications');
        $this->assertSame(5, $paginator->count());
        $this->assertSame(8, $paginator->total());
        $focused = $paginator->first();
        $first->assertSee(route('landowner.applications.index', ['application' => $focused->id]) . '#application-' . $focused->id, false);

        $second = $this->get(route('landowner.dashboard', ['compliance_page' => 2]));
        $second->assertOk()->assertSee('Showing 6–8 of 8')->assertDontSee('Required evidence 9');
        $this->assertSame(3, $second->viewData('complianceApplications')->count());
        $this->assertSame(8, $paginator->pluck('id')->merge($second->viewData('complianceApplications')->pluck('id'))->unique()->count());
    }
}
