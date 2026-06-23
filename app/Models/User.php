<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements \Tymon\JWTAuth\Contracts\JWTSubject
{
    use HasFactory, Notifiable, \App\Traits\HasPublicUuid, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'email',
        'password_hash',
        'full_name',
        'phone',
        'role',
        'is_active',
        'status',
        'plan_type',
        'city_id',
        'logo_url',
        'signature_url',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_hash' => 'hashed',
            'is_active' => 'boolean',
            'status' => \App\Enums\AccountStatus::class,
            'role' => \App\Enums\UserRole::class,
            'plan_type' => \App\Enums\PlanType::class,
        ];
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
            'role' => $this->role,
            'isActive' => $this->is_active,
        ];
    }

    public function specialties()
    {
        return $this->belongsToMany(Specialty::class, 'doctor_specialty')
                    ->using(DoctorSpecialty::class)
                    ->withTimestamps();
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function providerProfile()
    {
        return $this->hasOne(ProviderProfile::class);
    }

    public function clinicBranchMembers()
    {
        return $this->hasMany(ClinicBranchMember::class);
    }
}
