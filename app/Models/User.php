<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, \App\Traits\HasPublicUuid, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'email',
        'password_hash',
        'full_name',
        'phone',
        'role',
        'is_active',
        'plan_type',
        'specialty',
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
            'role' => \App\Enums\UserRole::class,
            'plan_type' => \App\Enums\PlanType::class,
        ];
    }
}
