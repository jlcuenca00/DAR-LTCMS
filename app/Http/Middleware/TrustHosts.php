<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Return exact trusted-host patterns after Laravel configuration is loaded.
     *
     * @return array<int, string>
     */
    public function hosts(): array
    {
        $hosts = (array) config('app.trusted_hosts', []);

        if ($hosts === []) {
            $applicationHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            $hosts = $applicationHost ? [$applicationHost] : [];

            if (! $this->app->environment('production')) {
                $hosts[] = 'localhost';
                $hosts[] = '127.0.0.1';
            }
        }

        $hosts = array_values(array_unique(array_filter(array_map(
            static fn ($host): string => strtolower(trim((string) $host)),
            $hosts
        ))));

        return array_map(
            static fn (string $host): string => '^'.preg_quote($host).'$',
            $hosts
        );
    }
}
