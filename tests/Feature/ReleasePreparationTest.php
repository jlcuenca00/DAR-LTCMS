<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ReleasePreparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_release_check_passes_only_when_data_and_production_configuration_are_clean(): void
    {
        $this->configureCleanProductionEnvironment();

        $exitCode = Artisan::call('dar:release-check', ['--json' => true]);
        $result = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($result['release_ready']);
        $this->assertTrue($result['read_only']);
        $this->assertTrue($result['data_integrity']['clean']);
        $this->assertTrue($result['production_readiness']['clean']);
    }

    public function test_final_release_check_fails_when_a_release_warning_remains(): void
    {
        $this->configureCleanProductionEnvironment();
        config(['mail.default' => 'log']);

        $exitCode = Artisan::call('dar:release-check', ['--json' => true]);
        $result = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(1, $exitCode);
        $this->assertFalse($result['release_ready']);
        $this->assertTrue($result['data_integrity']['clean']);
        $this->assertFalse($result['production_readiness']['clean']);
        $this->assertSame(
            'warning',
            collect($result['production_readiness']['issues'])->firstWhere('code', 'mail_not_deliverable')['severity']
        );
    }

    public function test_release_documentation_preserves_private_data_and_requires_backup_before_v1(): void
    {
        $guide = file_get_contents(base_path('docs/RELEASE_PREPARATION.md'));
        $deployment = file_get_contents(base_path('.github/workflows/deploy.yml'));

        $this->assertStringContainsString('pg_dump -Fc', $guide);
        $this->assertStringContainsString('php artisan dar:release-check', $guide);
        $this->assertStringContainsString('storage/app/private', $guide);
        $this->assertStringContainsString('storage/app/public', $guide);
        $this->assertStringContainsString('.release-commit', $guide);
        $this->assertStringContainsString('v1.0.0', $guide);
        $this->assertStringContainsString('does **not** automatically transfer land ownership', $guide);

        $this->assertStringContainsString('/storage/backups/', $deployment);
        $this->assertStringContainsString('/storage/app/private/', $deployment);
        $this->assertStringContainsString('/storage/app/public/', $deployment);
        $this->assertStringContainsString('/.env,', $deployment);
        $this->assertStringContainsString("printf '%s\\n'", $deployment);
        $this->assertStringContainsString('> .release-commit', $deployment);
    }

    private function configureCleanProductionEnvironment(): void
    {
        File::ensureDirectoryExists(storage_path('app/private'));
        File::ensureDirectoryExists(storage_path('app/public'));

        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.url' => 'https://darltcms.me',
            'app.key' => 'base64:'.base64_encode(str_repeat('r', 32)),
            'session.driver' => 'database',
            'session.encrypt' => true,
            'session.secure' => true,
            'session.http_only' => true,
            'session.same_site' => 'lax',
            'filesystems.default' => 'local',
            'filesystems.disks.public.url' => 'https://darltcms.me/staff/protected-storage',
            'mail.default' => 'smtp',
            'logging.default' => 'single',
            'logging.channels.single.level' => 'warning',
        ]);
    }
}
