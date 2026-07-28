<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalSupplyStaff extends Model
{
    protected $fillable = [
        'user_id',
        'provider_profile_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => \App\Enums\MedicalSupplyRole::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function providerProfile()
    {
        return $this->belongsTo(ProviderProfile::class);
    }
}
