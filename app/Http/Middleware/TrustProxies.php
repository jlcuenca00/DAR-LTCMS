<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Only trust the forwarded headers DAR-LTCMS uses for client/scheme/host/port.
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO;

    /**
     * Resolve deployment proxy addresses after Laravel configuration is loaded.
     *
     * @return array<int, string>|string|null
     */
    protected function proxies()
    {
        $configured = array_values(array_filter(array_map(
            static fn ($proxy): string => trim((string) $proxy),
            (array) config('app.trusted_proxies', [])
        )));

        if ($configured === []) {
            return parent::proxies();
        }

        if (count($configured) === 1 && in_array($configured[0], ['*', '**'], true)) {
            return $configured[0];
        }

        return $configured;
    }
}
