<?php

namespace Tests\Feature;

use App\Models\Parcel;
use App\Models\RequiredDocument;
use App\Models\User;
use Database\Seeders\BarebonesTesterSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use LogicException;
use Tests\TestCase;

class SupportingArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_database_seeder_preserves_working_records_and_only_seeds_reference_configuration(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $parcel = Parcel::create([
            'parcel_code' => 'DEFAULT-SEED-SAFETY-001',
            'province' => 'Negros Oriental',
            'area_hectares' => 1.2500,
            'status' => 'active',
        ]);

        $exitCode = Artisan::call('db:seed', [
            '--class' => DatabaseSeeder::class,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('parcels', ['id' => $parcel->id]);
        $this->assertGreaterThan(0, RequiredDocument::query()->count());
    }

    public function test_barebones_tester_seeder_refuses_to_run_in_production(): void
    {
        $originalEnvironment = $this->app->environment();
        $this->app->detectEnvironment(fn (): string => 'production');

        try {
            (new BarebonesTesterSeeder())->run();
            $this->fail('Expected the destructive tester seeder to be rejected in production.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('must never run in production', $exception->getMessage());
        } finally {
            $this->app->detectEnvironment(fn (): string => $originalEnvironment);
        }
    }
}
