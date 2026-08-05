<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcedPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_flagged_user_cannot_open_dashboard_before_changing_password(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'must_change_password' => true,
            'password_changed_at' => now(),
        ]);

        $version = $user->password_changed_at->format('Y-m-d H:i:s.u');

        $this->actingAs($user)
            ->withSession(['auth_password_changed_at' => $version])
            ->get(route('staff.dashboard'))
            ->assertRedirect(route('password.required'));
    }

    public function test_flagged_user_can_replace_temporary_password(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'password' => Hash::make('temporary123'),
            'must_change_password' => true,
            'password_changed_at' => now(),
        ]);

        $version = $user->password_changed_at->format('Y-m-d H:i:s.u');

        $response = $this->actingAs($user)
            ->withSession(['auth_password_changed_at' => $version])
            ->put(route('password.required.update'), [
                'password' => 'Private-New-123!',
                'password_confirmation' => 'Private-New-123!',
            ]);

        $response->assertRedirect(route('landowner.dashboard'));

        $user->refresh();
        $this->assertTrue(Hash::check('Private-New-123!', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'action' => 'password_changed_after_reset',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }
}
