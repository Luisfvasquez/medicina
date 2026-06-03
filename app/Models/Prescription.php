<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use \App\Traits\HasPublicUuid, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'user_id',
        'patient_id',
        'consultation_id',
        'clinic_id',
        'date',
        'expiration_date',
        'notes',
        'public_token',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'expiration_date' => 'datetime',
            'status' => \App\Enums\RxStatus::class,
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

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }
}
