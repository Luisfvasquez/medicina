<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'name',
        'rif',
        'address',
        'logo_url',
        'website',
        'phone',
    ];

    public function members()
    {
        return $this->hasMany(ClinicMember::class);
    }
}
