<?php

namespace App\Models;

use App\Traits\HasPublicUuid;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class PatientAccount extends Authenticatable implements JWTSubject
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

    protected $hidden = [
        'password_hash',
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

    public function getAvatarUrlAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        $disk = 'r2_images'; // Force r2_images for avatars

        try {
            return \Illuminate\Support\Facades\Storage::disk($disk)->temporaryUrl(
                $value,
                \Carbon\Carbon::now()->addDays(7)
            );
        } catch (\Throwable $e) {
            return \Illuminate\Support\Facades\Storage::disk($disk)->url($value);
        }
    }
}
