<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicBranch extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'clinic_id',
        'name',
        'address',
        'city_id',
        'phone',
        'is_main_branch',
        'latitude',
        'longitude',
        'google_maps_url',
        'observations',
    ];

    protected $casts = [
        'is_main_branch' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function members()
    {
        return $this->hasMany(ClinicBranchMember::class);
    }
}
