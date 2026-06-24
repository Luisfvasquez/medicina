<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescription extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'patient_id',
        'consultation_id',
        'clinic_branch_id',
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

    public function clinicBranch()
    {
        return $this->belongsTo(ClinicBranch::class, 'clinic_branch_id');
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function quoteRequests()
    {
        return $this->hasMany(QuoteRequest::class);
    }
}
