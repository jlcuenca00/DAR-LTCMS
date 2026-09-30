<?php

namespace App\Policies;

use App\Models\Parcel;
use App\Models\User;

class ParcelPolicy
{
    public function view(User $user, Parcel $parcel): bool
    {
        if ($user->isStaff() || $user->isGeodetic()) {
            return true;
        }

        if (! $user->isLandowner()) {
            return false;
        }

        $landowner = $user->landowner;

        return $landowner
            ? $landowner->landholdings()->where('parcel_id', $parcel->id)->exists()
            : false;
    }

    public function updateGeometry(User $user, Parcel $parcel): bool
    {
        return $user->isGeodetic();
    }
}
