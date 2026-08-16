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
                'version' => 1,
                'seen' => false,
            ]);

        $this->actingAs($user)
            ->patchJson('/onboarding-tours/landowner_portal', [
                'version' => 1,
                'status' => 'completed',
            ])
            ->assertOk()
            ->assertJson([
                'saved' => true,
                'tour_key' => 'landowner_portal',
                'version' => 1,
                'status' => 'completed',
            ]);

        $user->refresh();

        $this->assertSame(1, data_get($user->onboarding_state, 'landowner_portal.version'));
        $this->assertSame('completed', data_get($user->onboarding_state, 'landowner_portal.status'));

        $this->actingAs($user)
            ->getJson('/onboarding-tours/landowner_portal')
            ->assertOk()
            ->assertJson(['seen' => true]);
    }

    public function test_other_roles_cannot_use_landowner_portal_tour_state(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $this->actingAs($staff)
            ->getJson('/onboarding-tours/landowner_portal')
            ->assertForbidden();

        $this->actingAs($staff)
            ->patchJson('/onboarding-tours/landowner_portal', [
                'version' => 1,
                'status' => 'skipped',
            ])
            ->assertForbidden();
    }
}
