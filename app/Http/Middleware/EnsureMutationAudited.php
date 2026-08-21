<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\LandTransferApplication;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMutationAudited
{
    private const MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Notification read-state is transient UI state rather than an administrative
     * record mutation, so it is intentionally excluded from the permanent audit trail.
     */
    private const EXCLUDED_ROUTES = [
        'notifications.read',
        'notifications.read-all',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array(strtoupper($request->method()), self::MUTATING_METHODS, true)) {
            return $next($request);
        }

        $requestId = AuditLogger::ensureRequestId($request);
        $actorIdBefore = $request->user()?->id;

        $response = $next($request);
        $routeName = $request->route()?->getName();

        if (in_array($routeName, self::EXCLUDED_ROUTES, true)) {
            return $response;
        }

        if (AuditLog::query()->where('request_id', $requestId)->exists()) {
            return $response;
        }

        [$application, $auditable] = $this->resolveContextModels($request);

        AuditLogger::record(
            'mutation_request_fallback',
            $application,
            $auditable,
            [
                'route_name' => $routeName,
                'http_method' => strtoupper($request->method()),
                'response_status' => $response->getStatusCode(),
                'input_fields' => array_values(array_unique(array_map(
                    static fn ($key): string => (string) $key,
                    array_keys($request->all())
                ))),
                'audit_classification' => 'fallback_for_mutating_request_without_domain_event',
            ],
            $request->user()?->id ?? $actorIdBefore
        );

        return $response;
    }

    /**
     * @return array{0: ?LandTransferApplication, 1: ?Model}
     */
    private function resolveContextModels(Request $request): array
    {
        $application = null;
        $auditable = null;
        $route = $request->route();

        if (! $route) {
            return [$application, $auditable];
        }

        foreach ($route->parameters() as $parameter) {
            if (! $parameter instanceof Model) {
                continue;
            }

            $auditable ??= $parameter;

            if ($parameter instanceof LandTransferApplication) {
                $application = $parameter;
                $auditable = $parameter;
                break;
            }
        }

        return [$application, $auditable];
    }
}
