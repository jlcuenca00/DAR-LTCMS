<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PresentationDemoAndUiPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_landing_page_matches_the_final_system_scope(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Land Transfer Clearance and Monitoring System');
        $response->assertSee('Pending Review by Legal Officer');
        $response->assertSee('Endorsed to LTI Division');
        $response->assertSee('Endorsed to Chief Legal');
        $response->assertSee('Endorsed to PARPO II');
        $response->assertSee('For Releasing');
        $response->assertSee('Released or Denied');
        $response->assertSee('LTC Form No. 5');
        $response->assertSee('does not automatically transfer land ownership');
        $response->assertSee('Landowners do not create clearance applications');
        $response->assertDontSee('This site is still undergoing development');
    }

    public function test_requested_parcel_lists_use_accessible_clickable_rows_without_action_columns(): void
    {
        $landownerView = file_get_contents(resource_path('views/landowner/parcels/index.blade.php'));
        $geodeticView = file_get_contents(resource_path('views/geodetic/parcels/index.blade.php'));
        $geodeticDashboard = file_get_contents(resource_path('views/dashboards/geodetic.blade.php'));
        $rowNavigation = file_get_contents(resource_path('views/components/record-row-navigation.blade.php'));

        $this->assertStringContainsString('data-record-row-href', $landownerView);
        $this->assertStringContainsString('data-record-row-href', $geodeticView);
        $this->assertStringContainsString('data-record-row-href', $geodeticDashboard);

        $this->assertStringNotContainsString('lo-parcel-action-column', $landownerView);
        $this->assertStringNotContainsString('geo-record-action-column', $geodeticView);
        $this->assertStringNotContainsString('>Action</th>', $landownerView);
        $this->assertStringNotContainsString('>Action</th>', $geodeticView);

        $this->assertStringContainsString("row.setAttribute('role', 'link')", $rowNavigation);
        $this->assertStringContainsString("row.setAttribute('tabindex', '0')", $rowNavigation);
        $this->assertStringContainsString("event.key !== 'Enter'", $rowNavigation);
        $this->assertStringContainsString("event.key !== ' '", $rowNavigation);
    }

    public function test_demo_provisioner_creates_only_staff_and_geodetic_login_accounts(): void
    {
        $before = [
            'landowners' => Landowner::query()->count(),
            'parcels' => Parcel::query()->count(),
            'landholdings' => Landholding::query()->count(),
            'applications' => LandTransferApplication::query()->count(),
        ];

        $this->artisan('dar:provision-demo-access', [
            '--staff-password' => 'StaffDemo321!Safe',
            '--geodetic-password' => 'GeoDemo321!Safe',
        ])->assertSuccessful();

        $staff = User::query()->where('email', 'staff.demo@dar-ltcms.local')->firstOrFail();
        $geodetic = User::query()->where('email', 'geodetic.demo@dar-ltcms.local')->firstOrFail();

        $this->assertSame('staff.demo', $staff->username);
        $this->assertSame(User::ROLE_STAFF, $staff->role);
        $this->assertTrue($staff->is_active);
        $this->assertFalse($staff->must_change_password);
        $this->assertTrue(Hash::check('StaffDemo321!Safe', $staff->password));

        $this->assertSame('geodetic.demo', $geodetic->username);
        $this->assertSame(User::ROLE_GEODETIC, $geodetic->role);
        $this->assertTrue($geodetic->is_active);
        $this->assertFalse($geodetic->must_change_password);
        $this->assertTrue(Hash::check('GeoDemo321!Safe', $geodetic->password));

        $this->assertSame($before['landowners'], Landowner::query()->count());
        $this->assertSame($before['parcels'], Parcel::query()->count());
        $this->assertSame($before['landholdings'], Landholding::query()->count());
        $this->assertSame($before['applications'], LandTransferApplication::query()->count());
        $this->assertSame(2, AuditLog::query()->where('action', 'presentation_demo_account_provisioned')->count());
    }

    public function test_demo_provisioner_is_idempotent_and_can_disable_accounts_without_deleting_them(): void
    {
        $first = [
            '--staff-password' => 'StaffDemo321!Safe',
            '--geodetic-password' => 'GeoDemo321!Safe',
        ];
        $second = [
            '--staff-password' => 'StaffDemo654!Safe',
            '--geodetic-password' => 'GeoDemo654!Safe',
        ];

        $this->artisan('dar:provision-demo-access', $first)->assertSuccessful();
        $this->artisan('dar:provision-demo-access', $second)->assertSuccessful();

        $this->assertSame(1, User::query()->where('email', 'staff.demo@dar-ltcms.local')->count());
        $this->assertSame(1, User::query()->where('email', 'geodetic.demo@dar-ltcms.local')->count());
        $this->assertTrue(Hash::check('StaffDemo654!Safe', User::query()->where('email', 'staff.demo@dar-ltcms.local')->value('password')));
        $this->assertTrue(Hash::check('GeoDemo654!Safe', User::query()->where('email', 'geodetic.demo@dar-ltcms.local')->value('password')));
        $this->assertSame(4, AuditLog::query()->where('action', 'presentation_demo_account_provisioned')->count());

        $this->artisan('dar:disable-demo-access')->assertSuccessful();

        $this->assertFalse((bool) User::query()->where('email', 'staff.demo@dar-ltcms.local')->value('is_active'));
        $this->assertFalse((bool) User::query()->where('email', 'geodetic.demo@dar-ltcms.local')->value('is_active'));
        $this->assertSame(2, User::query()->whereIn('email', [
            'staff.demo@dar-ltcms.local',
            'geodetic.demo@dar-ltcms.local',
        ])->count());
        $this->assertSame(2, AuditLog::query()->where('action', 'presentation_demo_account_disabled')->count());
    }

    public function test_demo_provisioner_rejects_weak_passwords(): void
    {
        $this->artisan('dar:provision-demo-access', [
            '--staff-password' => 'weak',
            '--geodetic-password' => 'also-weak',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'staff.demo@dar-ltcms.local']);
        $this->assertDatabaseMissing('users', ['email' => 'geodetic.demo@dar-ltcms.local']);
        $this->assertSame(0, AuditLog::query()->whereIn('action', [
            'presentation_demo_account_provisioned',
            'presentation_demo_account_disabled',
        ])->count());
    }
}
