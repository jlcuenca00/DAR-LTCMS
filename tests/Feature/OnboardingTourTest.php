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
                'version' => 2,
                'seen' => false,
            ]);

        $this->actingAs($user)
            ->patchJson('/onboarding-tours/landowner_portal', [
                'version' => 2,
                'status' => 'completed',
            ])
            ->assertOk()
            ->assertJson([
                'saved' => true,
                'tour_key' => 'landowner_portal',
                'version' => 2,
                'status' => 'completed',
            ]);

        $user->refresh();

        $this->assertSame(2, data_get($user->onboarding_state, 'landowner_portal.version'));
        $this->assertSame('completed', data_get($user->onboarding_state, 'landowner_portal.status'));

        $this->actingAs($user)
            ->getJson('/onboarding-tours/landowner_portal')
            ->assertOk()
            ->assertJson(['seen' => true]);
    }

    public function test_older_tour_version_is_offered_again_when_current_version_increases(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'onboarding_state' => [
                'landowner_portal' => [
                    'version' => 1,
                    'status' => 'completed',
                    'seen_at' => now()->subDay()->toIso8601String(),
                ],
            ],
        ]);

        $this->actingAs($user)
            ->getJson('/onboarding-tours/landowner_portal')
            ->assertOk()
            ->assertJson([
                'version' => 2,
                'seen' => false,
            ]);
    }

    public function test_other_roles_cannot_use_landowner_portal_tour_state(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $this->actingAs($staff)
            ->getJson('/onboarding-tours/landowner_portal')
            ->assertForbidden();

        $this->actingAs($staff)
            ->patchJson('/onboarding-tours/landowner_portal', [
                'version' => 2,
                'status' => 'skipped',
            ])
            ->assertForbidden();
    }
}
