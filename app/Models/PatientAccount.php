<?php

namespace App\Models;

use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;

class PatientAccount extends Model implements JWTSubject
{
    use HasPublicUuid;

    protected $fillable = [
        'phone',
        'email',
        'password_hash',
        'full_name',
        'national_id',
        'username',
        'city_id',
        'avatar_url',
        'is_active',
        'status',
    ];

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'status' => \App\Enums\AccountStatus::class,
        ];
    }
}
