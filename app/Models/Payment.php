<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

    protected $appends = ['receipt_url'];

    protected $fillable = [
        'uuid',
        'invoice_id',
        'amount',
        'method',
        'reference',
        'paid_at',
        'notes',
        'receipt_path',
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

    public function getReceiptUrlAttribute(): ?string
    {
        if (!$this->receipt_path) {
            return null;
        }

        $disk = config('filesystems.default');

        if ($disk === 'r2' || $disk === 's3') {
            try {
                return \Illuminate\Support\Facades\Storage::disk($disk)->temporaryUrl(
                    $this->receipt_path,
                    \Carbon\Carbon::now()->addMinutes(15)
                );
            } catch (\Throwable $e) {
                return \Illuminate\Support\Facades\Storage::disk($disk)->url($this->receipt_path);
            }
        }

        return \Illuminate\Support\Facades\Storage::disk($disk)->url($this->receipt_path);
    }
}
