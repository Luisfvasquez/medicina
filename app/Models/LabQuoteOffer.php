<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabQuoteOffer extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'lab_request_id',
        'provider_profile_id',
        'total_price_base',
        'currency',
        'prices_manual',
        'items_detail',
        'comments',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_price_base' => 'decimal:2',
            'prices_manual' => 'array',
            'items_detail' => 'array',
        ];
    }

    public function labRequest()
    {
        return $this->belongsTo(LabRequest::class);
    }

    public function providerProfile()
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function appointments()
    {
        return $this->hasMany(LabAppointment::class);
    }
}
