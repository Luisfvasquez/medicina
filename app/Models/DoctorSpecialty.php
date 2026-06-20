<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DoctorSpecialty extends Pivot
{
    use HasUuids;

    protected $table = 'doctor_specialty';
    public $incrementing = false;
    protected $keyType = 'string';
}
