<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'uuid',
        'name',
        'rif',
        'logo_url',
        'website',
    ];

    public function branches()
    {
        return $this->hasMany(ClinicBranch::class);
    }
}
