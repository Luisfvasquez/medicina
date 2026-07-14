<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderService extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'service_id',
        'provider_id',
        'provider_type',
        'price',
        'duration_minutes',
        'is_standalone_bookable',
        'is_active',
        'custom_name',
        'custom_description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_minutes' => 'integer',
            'is_standalone_bookable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function provider()
    {
        return $this->morphTo();
    }
}
