<?php

namespace Tests\Feature;

use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_staff_roles_cannot_use_user_management_mutation_routes(): void
    {
        $target = User::factory()->create([
            'name' => 'Protected Staff Account',
            'username' => 'protected_staff',
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        foreach ([User::ROLE_LANDOWNER, User::ROLE_GEODETIC] as $role) {
            $attacker = User::factory()->create([
                'role' => $role,
                'is_active' => true,
            ]);

            $this->actingAs($attacker)
                ->post(route('staff.users.store'), [
                    'name' => 'Unauthorized Account',
                    'username' => 'unauthorized_' . $role,
                    'password' => 'Temporary-123!',
                    'password_confirmation' => 'Temporary-123!',
                    'role' => User::ROLE_STAFF,
                    'is_active' => '1',
                ])
                ->assertForbidden();

            $this->actingAs($attacker)
                ->put(route('staff.users.update', $target), [
                    'name' => 'Tampered Name',
                    'username' => 'tampered_staff',
                    'role' => $role,
                    'is_active' => '0',
                ])
                ->assertForbidden();

            $this->actingAs($attacker)
                ->post(route('staff.users.reset-password', $target))
                ->assertForbidden();
        }

        $target->refresh();
        $this->assertSame('Protected Staff Account', $target->name);
        $this->assertSame('protected_staff', $target->username);
        $this->assertSame(User::ROLE_STAFF, $target->role);
        $this->assertTrue($target->is_active);
        $this->assertDatabaseMissing('users', ['name' => 'Unauthorized Account']);
    }

    public function test_non_staff_roles_cannot_mutate_staff_parcel_records_by_direct_request(): void
    {
        $parcel = Parcel::create([
            'parcel_code' => 'SEC-RBAC-PARCEL-001',
            'title_no' => 'T-SECURE-001',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.2500,
            'status' => 'active',
        ]);

        foreach ([User::ROLE_LANDOWNER, User::ROLE_GEODETIC] as $role) {
            $attacker = User::factory()->create([
                'role' => $role,
                'is_active' => true,
            ]);

            $this->actingAs($attacker)
                ->patch(route('staff.records.parcels.update', $parcel), [
                    'parcel_code' => 'TAMPERED-' . $role,
                    'title_no' => 'T-TAMPERED-999',
                    'municipality' => 'Dumaguete City',
                    'barangay' => 'Bantayan',
                    'province' => 'Negros Oriental',
                    'area_hectares' => 999,
                    'status' => 'inactive',
                ])
                ->assertForbidden();
        }

        $parcel->refresh();
        $this->assertSame('SEC-RBAC-PARCEL-001', $parcel->parcel_code);
        $this->assertSame('T-SECURE-001', $parcel->title_no);
        $this->assertSame('active', $parcel->status);
        $this->assertEquals(1.2500, (float) $parcel->area_hectares);
    }
}
