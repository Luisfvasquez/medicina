<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasPublicUuid;

class ProviderProfile extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'type',
        'commercial_name',
        'rif',
        'address',
        'city_id',
        'phone',
        'is_open',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'type' => \App\Enums\ProviderType::class,
            'is_open' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function branches()
    {
        return $this->hasMany(ProviderBranch::class);
    }
}
