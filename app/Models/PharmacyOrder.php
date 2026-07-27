<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasPublicUuid;

class PharmacyOrder extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'quote_offer_id',
        'provider_id',
        'patient_account_id',
        'status',
        'selected_currency_payment',
        'stock_deducted',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'selected_currency_payment' => 'array',
            'stock_deducted' => 'boolean',
            'confirmed_at' => 'datetime',
        ];
    }

    public function providerProfile()
    {
        return $this->belongsTo(ProviderProfile::class, 'provider_id');
    }

    public function patientAccount()
    {
        return $this->belongsTo(PatientAccount::class);
    }

    public function quoteOffer()
    {
        return $this->belongsTo(QuoteOffer::class);
    }

    public function items()
    {
        return $this->hasMany(PharmacyOrderItem::class);
    }
}
