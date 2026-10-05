<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class DeploymentFinalizationTest extends TestCase
{
    public function test_successful_http_check_publishes_revision_and_keeps_application_open(): void
    {
        [$status, $commands, $revision] = $this->finalize(0);
        $this->assertSame(0, $status);
        $this->assertSame(['artisan up', 'artisan dar:check-deployment-http'], $commands);
        $this->assertSame(str_repeat('b', 40)."\n", $revision);
    }

    public function test_failed_http_check_restores_maintenance_and_preserves_previous_revision(): void
    {
        [$status, $commands, $revision] = $this->finalize(1);
        $this->assertSame(1, $status);
        $this->assertSame(['artisan up', 'artisan dar:check-deployment-http', 'artisan down --retry=60 --render=errors::503'], $commands);
        $this->assertSame(str_repeat('a', 40)."\n", $revision);
    }

    public function test_failure_to_reopen_skips_http_check_and_preserves_previous_revision(): void
    {
        [$status, $commands, $revision] = $this->finalize(0, 1);
        $this->assertSame(1, $status);
        $this->assertSame(['artisan up', 'artisan down --retry=60 --render=errors::503'], $commands);
        $this->assertSame(str_repeat('a', 40)."\n", $revision);
    }

    private function finalize(int $smokeStatus, int $upStatus = 0): array
    {
        $directory = sys_get_temp_dir().'/dar-deploy-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($directory.'/bin');
        try {
            File::put($directory.'/.release-commit', str_repeat('a', 40)."\n");
            File::put($directory.'/bin/php', <<<'BASH'
#!/usr/bin/env bash
echo "$*" >> "$COMMAND_LOG"
case "$*" in
  "artisan up") exit "$UP_STATUS" ;;
  "artisan dar:check-deployment-http") exit "$SMOKE_STATUS" ;;
  *) exit 0 ;;
esac
BASH);
            chmod($directory.'/bin/php', 0755);
            $process = new Process(['bash', base_path('scripts/finalize_production_deployment.sh'), $directory, str_repeat('b', 40)], null, [
                'PATH' => $directory.'/bin:'.getenv('PATH'),
                'COMMAND_LOG' => $directory.'/commands',
                'UP_STATUS' => (string) $upStatus,
                'SMOKE_STATUS' => (string) $smokeStatus,
            ]);
            $status = $process->run();

            return [$status, file($directory.'/commands', FILE_IGNORE_NEW_LINES), File::get($directory.'/.release-commit')];
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
