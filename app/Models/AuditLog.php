<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditLog extends Model
{
    protected $fillable = [
        'event_uuid',
        'request_id',
        'actor_user_id',
        'actor_name_snapshot',
        'actor_username_snapshot',
        'actor_role_snapshot',
        'land_transfer_application_id',
        'application_code_snapshot',
        'auditable_type',
        'auditable_id',
        'action',
        'metadata',
        'route_name',
        'http_method',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('DAR-LTCMS audit logs are append-only and cannot be updated.');
        });

        static::deleting(function (): never {
            throw new LogicException('DAR-LTCMS audit logs are append-only and cannot be deleted.');
        });
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function application()
    {
        return $this->belongsTo(LandTransferApplication::class, 'land_transfer_application_id');
    }

    public function auditable()
    {
        return $this->morphTo();
    }
}
