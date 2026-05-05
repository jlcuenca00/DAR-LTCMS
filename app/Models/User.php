<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_STAFF = 'staff';
    public const ROLE_LANDOWNER = 'landowner';
    public const ROLE_GEODETIC = 'geodetic';

    public const ROLES = [
        self::ROLE_STAFF,
        self::ROLE_LANDOWNER,
        self::ROLE_GEODETIC,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function landowner()
    {
        return $this->hasOne(Landowner::class);
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isLandowner(): bool
    {
        return $this->role === self::ROLE_LANDOWNER;
    }

    public function isGeodetic(): bool
    {
        return $this->role === self::ROLE_GEODETIC;
    }
}