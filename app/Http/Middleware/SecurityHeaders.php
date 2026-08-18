<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (app()->environment('production')) {
            // Current UI still contains inline Blade styles/scripts, so this is a
            // compatibility CSP baseline. Blade escaping and safe DOM handling
            // remain mandatory; future asset extraction can remove unsafe-inline.
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'self'",
                "object-src 'none'",
                "script-src 'self' 'unsafe-inline' https://unpkg.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://unpkg.com",
                "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
                "img-src 'self' data: blob: https://unpkg.com https://*.basemaps.cartocdn.com",
                "connect-src 'self'",
                "worker-src 'self' blob:",
            ]));
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        $isHtml = str_starts_with(strtolower($contentType), 'text/html');
        $isSensitiveAuthPage = $request->routeIs(
            'login',
            'password.request',
            'password.recovery.*',
            'password.required',
            'password.required.update',
            'password.confirm'
        );

        // Never cache authenticated pages or guest authentication/recovery forms.
        // Recovery forms contain session-bound CSRF tokens, so caching an older
        // response can surface a stale token and cause a 419 Page Expired error.
        if ($isHtml && ($request->user() || $isSensitiveAuthPage)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
