<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicMember extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'clinic_id',
        'user_id',
        'role',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'role' => \App\Enums\ClinicRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
