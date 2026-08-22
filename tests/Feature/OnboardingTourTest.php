<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_landowner_can_read_and_save_current_portal_tour_state(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_LANDOWNER]);

        $this->actingAs($user)
            ->getJson('/onboarding-tours/landowner_portal')
            ->assertOk()
            ->assertJson([
                'tour_key' => 'landowner_portal',
                'version' => 3,
                'seen' => false,
            ]);

        $this->actingAs($user)
            ->patchJson('/onboarding-tours/landowner_portal', [
                'version' => 3,
                'status' => 'completed',
            ])
            ->assertOk()
            ->assertJson([
                'saved' => true,
                'tour_key' => 'landowner_portal',
                'version' => 3,
                'status' => 'completed',
            ]);

        $user->refresh();

        $this->assertSame(3, data_get($user->onboarding_state, 'landowner_portal.version'));
        $this->assertSame('completed', data_get($user->onboarding_state, 'landowner_portal.status'));

        $this->actingAs($user)
            ->getJson('/onboarding-tours/landowner_portal')
            ->assertOk()
            ->assertJson(['seen' => true]);
    }

    public function test_staff_can_read_and_save_staff_portal_tour_state(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF]);

        $this->actingAs($user)
            ->getJson('/onboarding-tours/staff_portal')
            ->assertOk()
            ->assertJson([
                'tour_key' => 'staff_portal',
                'version' => 1,
                'seen' => false,
            ]);

        $this->actingAs($user)
            ->patchJson('/onboarding-tours/staff_portal', [
                'version' => 1,
                'status' => 'completed',
            ])
            ->assertOk()
            ->assertJson([
                'saved' => true,
                'tour_key' => 'staff_portal',
                'version' => 1,
                'status' => 'completed',
            ]);

        $user->refresh();
        $this->assertSame(1, data_get($user->onboarding_state, 'staff_portal.version'));
        $this->assertSame('completed', data_get($user->onboarding_state, 'staff_portal.status'));
    }

    public function test_geodetic_can_read_and_save_geodetic_portal_tour_state(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_GEODETIC]);

        $this->actingAs($user)
            ->getJson('/onboarding-tours/geodetic_portal')
            ->assertOk()
            ->assertJson([
                'tour_key' => 'geodetic_portal',
                'version' => 1,
                'seen' => false,
            ]);

        $this->actingAs($user)
            ->patchJson('/onboarding-tours/geodetic_portal', [
                'version' => 1,
                'status' => 'skipped',
            ])
            ->assertOk()
            ->assertJson([
                'saved' => true,
                'tour_key' => 'geodetic_portal',
                'version' => 1,
                'status' => 'skipped',
            ]);

        $user->refresh();
        $this->assertSame(1, data_get($user->onboarding_state, 'geodetic_portal.version'));
        $this->assertSame('skipped', data_get($user->onboarding_state, 'geodetic_portal.status'));
    }

    public function test_older_landowner_tour_version_is_offered_again_when_current_version_increases(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'onboarding_state' => [
                'landowner_portal' => [
                    'version' => 2,
                    'status' => 'completed',
                    'seen_at' => now()->subDay()->toIso8601String(),
                ],
            ],
        ]);

        $this->actingAs($user)
            ->getJson('/onboarding-tours/landowner_portal')
            ->assertOk()
            ->assertJson([
                'version' => 3,
                'seen' => false,
            ]);
    }

    public function test_tour_state_is_restricted_to_the_matching_role(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $landowner = User::factory()->create(['role' => User::ROLE_LANDOWNER]);
        $geodetic = User::factory()->create(['role' => User::ROLE_GEODETIC]);

        $this->actingAs($staff)
            ->getJson('/onboarding-tours/landowner_portal')
            ->assertForbidden();

        $this->actingAs($staff)
            ->getJson('/onboarding-tours/geodetic_portal')
            ->assertForbidden();

        $this->actingAs($landowner)
            ->getJson('/onboarding-tours/staff_portal')
            ->assertForbidden();

        $this->actingAs($geodetic)
            ->patchJson('/onboarding-tours/staff_portal', [
                'version' => 1,
                'status' => 'skipped',
            ])
            ->assertForbidden();
    }

    public function test_staff_and_geodetic_tour_scripts_are_loaded_and_role_specific(): void
    {
        $bootstrap = file_get_contents(resource_path('js/bootstrap.js'));
        $roleTours = file_get_contents(resource_path('js/role-onboarding-tours.js'));
        $mobilePolish = file_get_contents(resource_path('js/mobile-portal-polish.js'));

        $this->assertStringContainsString("import './role-onboarding-tours';", $bootstrap);
        $this->assertStringContainsString('staff_portal:', $roleTours);
        $this->assertStringContainsString('geodetic_portal:', $roleTours);
        $this->assertStringContainsString("roleRoot: '.staff-shell'", $roleTours);
        $this->assertStringContainsString("roleRoot: '.geo-shell'", $roleTours);
        $this->assertStringContainsString("help: '[data-onboarding-help=\"staff_portal\"]'", $mobilePolish);
        $this->assertStringContainsString("help: '[data-onboarding-help=\"geodetic_portal\"]'", $mobilePolish);
        $this->assertStringNotContainsString('approve', substr(
            $roleTours,
            strpos($roleTours, 'geodetic_portal:'),
        ));
    }
}
