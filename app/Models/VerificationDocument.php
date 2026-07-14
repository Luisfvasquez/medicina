<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VerificationDocument extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'type',
        'file_url',
        'status',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'type' => \App\Enums\DocVerificationType::class,
            'status' => \App\Enums\VerificationStatus::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFileUrlAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        $disk = config('filesystems.default');

        if ($disk === 'r2' || $disk === 's3') {
            try {
                return \Illuminate\Support\Facades\Storage::disk($disk)->temporaryUrl(
                    $value,
                    \Carbon\Carbon::now()->addMinutes(15)
                );
            } catch (\Throwable $e) {
                return \Illuminate\Support\Facades\Storage::disk($disk)->url($value);
            }
        }

        return \Illuminate\Support\Facades\Storage::disk($disk)->url($value);
    }
}
