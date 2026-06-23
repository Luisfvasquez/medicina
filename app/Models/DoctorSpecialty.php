<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Traits\HasPublicUuid;

class DoctorSpecialty extends Pivot
{
    use HasPublicUuid;
    
    protected $table = 'doctor_specialty';
    protected $fillable = ['uuid', 'user_id', 'specialty_id'];
}
