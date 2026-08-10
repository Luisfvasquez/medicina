<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PharmacyInventoryBatchItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    protected $casts = [
        'expiration_date' => 'date',
        'unit_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function batch()
    {
        return $this->belongsTo(PharmacyInventoryBatch::class, 'pharmacy_inventory_batch_id');
    }

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }
}
