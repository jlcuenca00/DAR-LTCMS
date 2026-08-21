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
        $configured = (array) config('app.trusted_proxies', []);

        return $configured !== [] ? $configured : parent::proxies();
    }
}
