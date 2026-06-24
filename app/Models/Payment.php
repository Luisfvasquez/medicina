<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'invoice_id',
        'amount',
        'method',
        'reference',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'method' => \App\Enums\PaymentMethod::class,
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
