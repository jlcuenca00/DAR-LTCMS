<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Landowner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_user_management_page(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.users.index'));

        $response->assertOk();
        $response->assertSee('User / Role Management');
        $response->assertSee('Staff-Managed User Accounts');
    }

    public function test_landowner_cannot_view_user_management_page(): void
    {
        $landownerUser = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);

        $response = $this->actingAs($landownerUser)
            ->get(route('staff.users.index'));

        $response->assertForbidden();
    }

    public function test_geodetic_cannot_view_user_management_page(): void
    {
        $geodeticUser = User::factory()->create([
            'role' => User::ROLE_GEODETIC,
            'is_active' => true,
        ]);

        $response = $this->actingAs($geodeticUser)
            ->get(route('staff.users.index'));

        $response->assertForbidden();
    }

    public function test_staff_can_create_geodetic_user_account(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staffUser)
            ->post(route('staff.users.store'), [
                'name' => 'Test Geodetic User',
                'email' => 'test.geodetic@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => User::ROLE_GEODETIC,
                'is_active' => '1',
                'landowner_id' => null,
            ]);

        $response->assertRedirect(route('staff.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'test.geodetic@example.com',
            'role' => User::ROLE_GEODETIC,
            'is_active' => true,
        ]);

        $createdUser = User::where('email', 'test.geodetic@example.com')->first();

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staffUser->id,
            'action' => 'user_created',
            'auditable_type' => User::class,
            'auditable_id' => $createdUser->id,
        ]);
    }

    public function test_staff_can_create_landowner_account_linked_to_landowner_record(): void
    {
        $staffUser = User::factory()->create([
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

        $response = $this->actingAs($staffUser)
            ->post(route('staff.users.store'), [
                'name' => 'Linked Landowner User',
                'email' => 'linked.landowner@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => User::ROLE_LANDOWNER,
                'is_active' => '1',
                'landowner_id' => $landowner->id,
            ]);

        $response->assertRedirect(route('staff.users.index'));
        $response->assertSessionHas('success');

        $createdUser = User::where('email', 'linked.landowner@example.com')->first();

        $this->assertNotNull($createdUser);

        $this->assertDatabaseHas('landowners', [
            'id' => $landowner->id,
            'user_id' => $createdUser->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staffUser->id,
            'action' => 'user_created',
            'auditable_type' => User::class,
            'auditable_id' => $createdUser->id,
        ]);
    }

    public function test_landowner_role_requires_linked_landowner_record(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staffUser)
            ->post(route('staff.users.store'), [
                'name' => 'Unlinked Landowner User',
                'email' => 'unlinked.landowner@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => User::ROLE_LANDOWNER,
                'is_active' => '1',
                'landowner_id' => null,
            ]);

        $response->assertSessionHasErrors('landowner_id');

        $this->assertDatabaseMissing('users', [
            'email' => 'unlinked.landowner@example.com',
        ]);
    }

    public function test_staff_cannot_change_own_role(): void
    {
        $staffUser = User::factory()->create([
            'name' => 'Current Staff',
            'email' => 'current.staff@example.com',
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staffUser)
            ->put(route('staff.users.update', $staffUser), [
                'name' => 'Current Staff',
                'email' => 'current.staff@example.com',
                'password' => null,
                'password_confirmation' => null,
                'role' => User::ROLE_GEODETIC,
                'is_active' => '1',
                'landowner_id' => null,
            ]);

        $response->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', [
            'id' => $staffUser->id,
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);
    }

    public function test_staff_cannot_deactivate_own_account(): void
    {
        $staffUser = User::factory()->create([
            'name' => 'Current Staff',
            'email' => 'current.staff@example.com',
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staffUser)
            ->put(route('staff.users.update', $staffUser), [
                'name' => 'Current Staff',
                'email' => 'current.staff@example.com',
                'password' => null,
                'password_confirmation' => null,
                'role' => User::ROLE_STAFF,
                'landowner_id' => null,
            ]);

        $response->assertSessionHasErrors('is_active');

        $this->assertDatabaseHas('users', [
            'id' => $staffUser->id,
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'name' => 'Inactive Staff',
            'email' => 'inactive.staff@example.com',
            'password' => 'password',
            'role' => User::ROLE_STAFF,
            'is_active' => false,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'inactive.staff@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_staff_can_update_other_user_status_and_role(): void
    {
        $staffUser = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $targetUser = User::factory()->create([
            'name' => 'Target User',
            'email' => 'target.user@example.com',
            'role' => User::ROLE_GEODETIC,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staffUser)
            ->put(route('staff.users.update', $targetUser), [
                'name' => 'Updated Target User',
                'email' => 'target.user@example.com',
                'password' => null,
                'password_confirmation' => null,
                'role' => User::ROLE_STAFF,
                'is_active' => '1',
                'landowner_id' => null,
            ]);

        $response->assertRedirect(route('staff.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Updated Target User',
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staffUser->id,
            'action' => 'user_updated',
            'auditable_type' => User::class,
            'auditable_id' => $targetUser->id,
        ]);
    }
}