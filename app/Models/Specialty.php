<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    protected $fillable = ['uuid', 'name', 'description'];

    public function doctors()
    {
        return $this->belongsToMany(User::class, 'doctor_specialty')
                    ->withTimestamps();
    }
}
