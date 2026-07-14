<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'category',
        'description',
        'base_price',
        'code',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
        ];
    }

    public function providers()
    {
        return $this->hasMany(ProviderService::class);
    }
}
