<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationDocument extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
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
}
