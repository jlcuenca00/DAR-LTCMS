<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductionBackupDatabaseConfiguration
{
    public function environment(): array
    {
        // getConfig() includes Laravel's DB_URL and write-connection resolution.
        // Constructing the connection does not open PDO or issue a query.
        $config = DB::connection()->getConfig();
        if (($config['driver'] ?? null) !== 'pgsql') {
            throw new RuntimeException('Production backup requires a PostgreSQL connection.');
        }

        $environment = [];
        foreach ([
            'PGHOST' => 'host', 'PGPORT' => 'port', 'PGDATABASE' => 'database',
            'PGUSER' => 'username', 'PGPASSWORD' => 'password', 'PGSSLMODE' => 'sslmode',
            'PGSSLROOTCERT' => 'sslrootcert', 'PGSSLCERT' => 'sslcert', 'PGSSLKEY' => 'sslkey',
        ] as $variable => $key) {
            $value = $config[$key] ?? '';
            if (! is_scalar($value) || str_contains((string) $value, "\0")) {
                throw new RuntimeException('Backup connection options must be scalar values without null bytes.');
            }
            $environment[$variable] = (string) $value;
        }

        foreach (['PGHOST', 'PGPORT', 'PGDATABASE', 'PGUSER'] as $required) {
            if ($environment[$required] === '') {
                throw new RuntimeException('The effective backup connection is incomplete.');
            }
        }

        return $environment;
    }
}
