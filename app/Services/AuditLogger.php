<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LandTransferApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLogger
{
    private const REDACTED = '[REDACTED]';

    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'new_password_confirmation',
        'temporary_password',
        'remember_token',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'cookie',
        'session_id',
        'otp',
        'verification_code',
        'recovery_code',
        'secret',
        'api_key',
    ];

    public static function record(
        string $action,
        ?LandTransferApplication $application = null,
        ?Model $auditable = null,
        array $metadata = [],
        ?int $actorUserId = null
    ): AuditLog {
        $request = request();
        $actor = self::resolveActor($actorUserId);
        $resolvedActorId = $actorUserId ?? $actor?->id ?? Auth::id();

        return AuditLog::create([
            'event_uuid' => (string) Str::uuid(),
            'request_id' => self::ensureRequestId($request),
            'actor_user_id' => $resolvedActorId,
            'actor_name_snapshot' => $actor?->name,
            'actor_username_snapshot' => $actor?->username,
            'actor_role_snapshot' => $actor?->role,
            'land_transfer_application_id' => $application?->id,
            'application_code_snapshot' => $application?->application_code,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'action' => $action,
            'metadata' => self::sanitizeMetadata($metadata),
            'route_name' => $request?->route()?->getName(),
            'http_method' => $request?->method(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    public static function ensureRequestId(?Request $request = null): string
    {
        $request ??= request();
        $existing = $request?->attributes->get('audit_request_id');

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $requestId = (string) Str::uuid();
        $request?->attributes->set('audit_request_id', $requestId);

        return $requestId;
    }

    public static function sanitizeMetadata(array $metadata): array
    {
        $sanitized = [];

        foreach ($metadata as $key => $value) {
            $normalizedKey = Str::lower((string) $key);

            if (in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
                $sanitized[$key] = self::REDACTED;
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeMetadata($value);
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $sanitized[$key] = is_string($value) && mb_strlen($value) > 8000
                    ? mb_substr($value, 0, 8000) . '…[TRUNCATED]'
                    : $value;
                continue;
            }

            $sanitized[$key] = '[NON_SCALAR_VALUE:' . $value::class . ']';
        }

        return $sanitized;
    }

    private static function resolveActor(?int $actorUserId): ?User
    {
        if ($actorUserId !== null) {
            return User::query()->find($actorUserId);
        }

        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
