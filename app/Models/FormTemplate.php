<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormTemplate extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'user_id',
        'specialty',
        'schema_json',
    ];

    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
