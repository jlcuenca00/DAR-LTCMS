<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'New-Password-123!',
                'password_confirmation' => 'New-Password-123!',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertTrue(Hash::check('New-Password-123!', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'action' => 'password_changed',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    public function test_current_password_is_required(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'New-Password-123!',
                'password_confirmation' => 'New-Password-123!',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');
    }

    public function test_new_password_must_be_different(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password');
    }
}
