<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'name', 'code'];

    public function states()
    {
        return $this->hasMany(State::class);
    }
}
