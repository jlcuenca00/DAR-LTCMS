<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;


class SystemNotification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'related_type',
        'related_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function related()
    {
        return $this->morphTo();
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }


    public function targetUrlFor(?User $viewer = null): string
    {
        $viewer ??= auth()->user();

        if (! $viewer) {
            return Route::has('notifications.index') ? route('notifications.index') : url('/');
        }

        if ($this->related_type === LandTransferApplication::class && $this->related_id) {
            if ($viewer->role === User::ROLE_STAFF && Route::has('staff.applications.show')) {
                return route('staff.applications.show', $this->related_id);
            }

            if ($viewer->role === User::ROLE_LANDOWNER) {
                if ($this->type === 'landowner_final_decision' && Route::has('landowner.applications.clearance.show')) {
                    return route('landowner.applications.clearance.show', $this->related_id);
                }

                if (Route::has('landowner.applications.index')) {
                    return route('landowner.applications.index');
                }
            }
        }

        if ($this->related_type === SourceRecordPackage::class && $this->related_id) {
            if ($viewer->role === User::ROLE_STAFF && Route::has('staff.source-record-packages.show')) {
                return route('staff.source-record-packages.show', $this->related_id);
            }

            if ($viewer->role === User::ROLE_GEODETIC && Route::has('geodetic.parcels.index')) {
                return route('geodetic.parcels.index');
            }
        }

        if ($this->related_type === Parcel::class && $this->related_id) {
            if ($viewer->role === User::ROLE_STAFF && Route::has('staff.records.parcels.show')) {
                return route('staff.records.parcels.show', $this->related_id);
            }

            if ($viewer->role === User::ROLE_GEODETIC && Route::has('geodetic.parcels.show')) {
                return route('geodetic.parcels.show', $this->related_id);
            }

            if ($viewer->role === User::ROLE_LANDOWNER && Route::has('landowner.parcels.show')) {
                return route('landowner.parcels.show', $this->related_id);
            }
        }

        return Route::has('notifications.index') ? route('notifications.index') : url('/');
    }

    public function markAsRead(): void
    {
        if ($this->read_at) {
            return;
        }

        $this->forceFill([
            'read_at' => now(),
        ])->save();
    }
}
