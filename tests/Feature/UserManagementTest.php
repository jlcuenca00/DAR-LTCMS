<?php

namespace Tests\Feature;

use App\Models\Landowner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_user_management_page(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('staff.users.index'))
            ->assertOk()
            ->assertSee('User / Role Management');
    }

    public function test_non_staff_users_cannot_view_user_management_page(): void
    {
        foreach ([User::ROLE_LANDOWNER, User::ROLE_GEODETIC] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'is_active' => true,
            ]);

            $this->actingAs($user)
                ->get(route('staff.users.index'))
                ->assertForbidden();
        }
    }

    public function test_staff_can_create_account_that_requires_initial_password_change(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staff)
            ->post(route('staff.users.store'), [
                'name' => 'Test Geodetic User',
                'username' => 'geo_test_01',
                'email' => null,
                'password' => 'Temporary-123!',
                'password_confirmation' => 'Temporary-123!',
                'role' => User::ROLE_GEODETIC,
                'is_active' => '1',
                'landowner_id' => null,
            ]);

        $response->assertRedirect(route('staff.users.index'));

        $created = User::where('username', 'geo_test_01')->firstOrFail();
        $this->assertTrue($created->must_change_password);
        $this->assertNotNull($created->password_changed_at);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staff->id,
            'action' => 'user_created',
            'auditable_type' => User::class,
            'auditable_id' => $created->id,
        ]);
    }

    public function test_staff_can_create_landowner_account_linked_to_landowner_record(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $landowner = Landowner::create([
            'first_name' => 'Linked',
            'middle_name' => 'Demo',
            'last_name' => 'Landowner',
            'municipality' => 'Dumaguete City',
            'province' => 'Negros Oriental',
        ]);

        $this->actingAs($staff)
            ->post(route('staff.users.store'), [
                'name' => 'Linked Landowner User',
                'username' => 'linked_landowner',
                'email' => null,
                'password' => 'Temporary-123!',
                'password_confirmation' => 'Temporary-123!',
                'role' => User::ROLE_LANDOWNER,
                'is_active' => '1',
                'landowner_id' => $landowner->id,
            ])
            ->assertRedirect(route('staff.users.index'));

        $created = User::where('username', 'linked_landowner')->firstOrFail();
        $this->assertDatabaseHas('landowners', [
            'id' => $landowner->id,
            'user_id' => $created->id,
        ]);
    }

    public function test_landowner_role_requires_linked_landowner_record(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->post(route('staff.users.store'), [
                'name' => 'Unlinked Landowner User',
                'username' => 'unlinked_landowner',
                'email' => null,
                'password' => 'Temporary-123!',
                'password_confirmation' => 'Temporary-123!',
                'role' => User::ROLE_LANDOWNER,
                'is_active' => '1',
                'landowner_id' => null,
            ])
            ->assertSessionHasErrors('landowner_id');
    }

    public function test_staff_cannot_change_own_role_or_deactivate_own_account(): void
    {
        $staff = User::factory()->create([
            'name' => 'Current Staff',
            'username' => 'current_staff',
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->put(route('staff.users.update', $staff), [
                'name' => $staff->name,
                'username' => $staff->username,
                'email' => $staff->email,
                'role' => User::ROLE_GEODETIC,
                'is_active' => '1',
                'landowner_id' => null,
            ])
            ->assertSessionHasErrors('role');

        $this->actingAs($staff)
            ->put(route('staff.users.update', $staff), [
                'name' => $staff->name,
                'username' => $staff->username,
                'email' => $staff->email,
                'role' => User::ROLE_STAFF,
                'landowner_id' => null,
            ])
            ->assertSessionHasErrors('is_active');
    }

    public function test_inactive_user_cannot_login_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'inactive_staff',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STAFF,
            'is_active' => false,
        ]);

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_staff_can_update_another_user_without_changing_password(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'name' => 'Target User',
            'username' => 'target_user',
            'role' => User::ROLE_GEODETIC,
            'is_active' => true,
        ]);

        $originalHash = $target->password;

        $this->actingAs($staff)
            ->put(route('staff.users.update', $target), [
                'name' => 'Updated Target User',
                'username' => 'updated_target',
                'email' => $target->email,
                'role' => User::ROLE_STAFF,
                'is_active' => '1',
                'landowner_id' => null,
            ])
            ->assertRedirect(route('staff.users.index'));

        $target->refresh();
        $this->assertSame($originalHash, $target->password);
        $this->assertSame('updated_target', $target->username);
        $this->assertSame(User::ROLE_STAFF, $target->role);
    }

    public function test_staff_can_generate_one_time_temporary_password_for_another_user(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'username' => 'reset_target',
            'must_change_password' => false,
            'password_changed_at' => null,
        ]);

        $oldHash = $target->password;

        $response = $this->actingAs($staff)
            ->post(route('staff.users.reset-password', $target));

        $response
            ->assertRedirect()
            ->assertSessionHas('temporary_password')
            ->assertSessionHas('temporary_password_username', 'reset_target');

        $temporaryPassword = $response->getSession()->get('temporary_password');
        $target->refresh();

        $this->assertNotSame($oldHash, $target->password);
        $this->assertTrue(Hash::check($temporaryPassword, $target->password));
        $this->assertTrue($target->must_change_password);
        $this->assertNotNull($target->password_changed_at);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staff->id,
            'action' => 'user_password_reset',
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
        ]);
    }

    public function test_staff_must_use_profile_to_change_own_password(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->post(route('staff.users.reset-password', $staff))
            ->assertSessionHas('error');
    }
}
