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

    protected function documentUrls(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value) {
                $paths = json_decode($value, true) ?? [];
                $urls = [];
                $disk = 'r2_documents';

                foreach ($paths as $path) {
                    // Si ya es una URL completa (migración antigua), la dejamos igual
                    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                        $urls[] = $path;
                        continue;
                    }

                    try {
                        $urls[] = \Illuminate\Support\Facades\Storage::disk($disk)->temporaryUrl(
                            $path,
                            \Carbon\Carbon::now()->addMinutes(60)
                        );
                    } catch (\Throwable $e) {
                        $urls[] = \Illuminate\Support\Facades\Storage::disk($disk)->url($path);
                    }
                }

                return $urls;
            },
            set: fn ($value) => json_encode($value)
        );
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
