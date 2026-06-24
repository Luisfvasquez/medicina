<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'uuid',
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
