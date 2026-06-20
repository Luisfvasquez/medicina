<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientAccount extends Model implements \Tymon\JWTAuth\Contracts\JWTSubject
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'phone',
        'email',
        'password_hash',
        'full_name',
        'national_id',
        'username',
        'city_id',
        'avatar_url',
    ];

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
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
}
