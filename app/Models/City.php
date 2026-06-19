<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'state_id', 'name'];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
