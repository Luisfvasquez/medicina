<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['uuid', 'state_id', 'name'];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
