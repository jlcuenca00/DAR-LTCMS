<?php

namespace App\Policies;

use App\Models\LandTransferApplication;
use App\Models\Landowner;
use App\Models\User;

class LandTransferApplicationPolicy
{
    public function viewDecisionOutput(User $user, LandTransferApplication $application): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        if (! $user->isLandowner()) {
            return false;
        }

        $landownerIds = Landowner::query()
            ->where('user_id', $user->id)
            ->pluck('id');

        return $landownerIds->contains(
            fn ($landownerId) => $application->isLinkedToLandowner((int) $landownerId)
        );
    }
}
