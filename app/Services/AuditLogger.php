<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LandTransferApplication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public static function record(
        string $action,
        ?LandTransferApplication $application = null,
        ?Model $auditable = null,
        array $metadata = [],
        ?int $actorUserId = null
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'actor_user_id' => $actorUserId ?? Auth::id(),
            'land_transfer_application_id' => $application?->id,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'action' => $action,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}