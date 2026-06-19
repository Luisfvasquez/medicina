<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'country_id', 'name'];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function cities()
    {
        return $this->hasMany(City::class);
    }
}
