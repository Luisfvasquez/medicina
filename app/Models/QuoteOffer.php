<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteOffer extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'quote_request_id',
        'provider_id',
        'price',
        'currency',
        'availability',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function quoteRequest()
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function providerProfile()
    {
        return $this->belongsTo(ProviderProfile::class, 'provider_id');
    }
}
