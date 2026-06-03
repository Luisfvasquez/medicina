<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionTemplate extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'user_id',
        'title',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(TemplateItem::class);
    }
}
