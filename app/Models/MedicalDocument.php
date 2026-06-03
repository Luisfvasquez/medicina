<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalDocument extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'user_id',
        'patient_id',
        'type',
        'content',
        'public_token',
    ];

    protected function casts(): array
    {
        return [
            'type' => \App\Enums\DocType::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
