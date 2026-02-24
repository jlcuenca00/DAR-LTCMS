<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Landowner extends Model
{
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'contact_number',
        'address_line',
        'barangay',
        'municipality',
        'province',
        'user_id',
    ];

    public function landholdings()
    {
        return $this->hasMany(Landholding::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name} {$this->suffix}");
    }
}