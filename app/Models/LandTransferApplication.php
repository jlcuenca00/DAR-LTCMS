<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandTransferApplication extends Model
{
    public function documents()
{
    return $this->hasMany(\App\Models\ApplicationDocument::class, 'land_transfer_application_id');
}
}
