<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'name', 'description'];

    public function doctors()
    {
        return $this->belongsToMany(User::class, 'doctor_specialty');
    }
}
