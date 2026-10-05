<?php

namespace Tests\Feature;

use App\Services\ProductionBackupDatabaseConfiguration;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ProductionBackupDatabaseConfigurationTest extends TestCase
{
    public function test_database_url_overrides_raw_settings_and_preserves_tls_and_special_characters(): void
    {
        $this->configure([
            'driver' => 'pgsql', 'host' => 'wrong-host', 'port' => 5432,
            'database' => 'wrong-db', 'username' => 'wrong-user', 'password' => 'wrong-password',
            'url' => 'postgresql://actual-user:'.rawurlencode("space ' $ password").'@actual-host:5544/actual-db?sslmode=verify-full&sslrootcert='.rawurlencode('/test/root cert'),
        ]);
        $environment = app(ProductionBackupDatabaseConfiguration::class)->environment();
        $this->assertSame('actual-host', $environment['PGHOST']);
        $this->assertSame('5544', $environment['PGPORT']);
        $this->assertSame('actual-db', $environment['PGDATABASE']);
        $this->assertSame('actual-user', $environment['PGUSER']);
        $this->assertSame("space ' $ password", $environment['PGPASSWORD']);
        $this->assertSame('verify-full', $environment['PGSSLMODE']);
        $this->assertSame('/test/root cert', $environment['PGSSLROOTCERT']);
        $this->assertInstanceOf(\Closure::class, DB::connection()->getRawPdo());
    }

    public function test_plain_configuration_uses_the_effective_default_connection(): void
    {
        $this->configure([
            'driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => 5432,
            'database' => 'backup-target', 'username' => 'backup-user',
            'password' => '', 'sslmode' => 'prefer',
        ]);
        $environment = app(ProductionBackupDatabaseConfiguration::class)->environment();
        $this->assertSame('backup-target', $environment['PGDATABASE']);
        $this->assertSame('', $environment['PGPASSWORD']);
        $this->assertSame('prefer', $environment['PGSSLMODE']);
    }

    public function test_non_postgresql_connection_is_rejected_before_dumping(): void
    {
        $this->configure(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->expectException(RuntimeException::class);
        app(ProductionBackupDatabaseConfiguration::class)->environment();
    }

    private function configure(array $configuration): void
    {
        config(['database.default' => 'backup_test', 'database.connections.backup_test' => $configuration]);
        DB::purge('backup_test');
    }
}
