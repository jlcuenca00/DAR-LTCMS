<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BarebonesTesterSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarebonesTesterLoginTest extends TestCase
{
    // The seeder truncates tables; use committed disposable fixtures, not an outer test transaction.
    use DatabaseMigrations;

    public function test_all_barebones_tester_accounts_can_sign_in_with_the_documented_usernames(): void
    {
        $this->seed(BarebonesTesterSeeder::class);

        $usernames = ['staff.tester', 'jay.staff', 'miles.staff', 'vea.staff', 'lloyd.staff'];
        $this->assertSame(5, User::count());
        $this->assertGreaterThan(0, DB::table('required_documents')->count());

        foreach ($usernames as $username) {
            $user = User::where('username', $username)->sole();
            $this->assertSame(User::ROLE_STAFF, $user->role);
            $this->assertTrue($user->is_active);

            $this->post('/login', ['username' => $username, 'password' => 'password'])
                ->assertSessionHasNoErrors()
                ->assertRedirect('/staff/dashboard');
            $this->assertAuthenticatedAs($user);
            $this->get('/staff/dashboard')->assertOk();
            $this->post('/logout')->assertRedirect('/');
            $this->assertGuest();
        }
    }

    public function test_production_guard_rejects_the_seeder_before_mutating_existing_accounts(): void
    {
        $user = User::factory()->create(['username' => 'preserved.staff']);
        $before = DB::table('users')->orderBy('id')->get()->toJson();
        $this->app->instance('env', 'production');

        try {
            $this->seed(BarebonesTesterSeeder::class);
            $this->fail('Production must reject destructive tester seeding.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('must never run in production', $exception->getMessage());
        } finally {
            $this->app->instance('env', 'testing');
        }

        $this->assertSame($before, DB::table('users')->orderBy('id')->get()->toJson());
        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => 'preserved.staff']);
    }
}
