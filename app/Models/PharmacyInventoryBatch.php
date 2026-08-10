<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasPublicUuid;

class PharmacyInventoryBatch extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'provider_id',
        'document_urls',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'document_urls' => 'array',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(ProviderProfile::class, 'provider_id');
    }

    public function items()
    {
        return $this->hasMany(PharmacyInventory::class);
    }
}
