<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientAccount extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'phone',
        'email',
        'password_hash',
        'full_name',
        'avatar_url',
    ];

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }
}
