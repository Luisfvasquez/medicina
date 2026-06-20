<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderBranch extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'provider_profile_id',
        'name',
        'address',
        'city_id',
        'phone',
        'is_open',
        'is_main_branch',
        'latitude',
        'longitude',
        'google_maps_url',
        'observations',
    ];

    protected $casts = [
        'is_open' => 'boolean',
        'is_main_branch' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function providerProfile()
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
