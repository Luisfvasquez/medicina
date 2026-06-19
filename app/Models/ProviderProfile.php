<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderProfile extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'user_id',
        'type',
        'commercial_name',
        'rif',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'type' => \App\Enums\ProviderType::class,
            'is_verified' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
