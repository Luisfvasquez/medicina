<?php

namespace App\Models;

use App\Enums\DocType;
use Illuminate\Database\Eloquent\Model;

class MedicalDocument extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'user_id',
        'patient_id',
        'patient_account_id',
        'clinic_branch_id',
        'type',
        'content',
        'public_token',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocType::class,
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

    public function patientAccount()
    {
        return $this->belongsTo(PatientAccount::class);
    }

    public function clinicBranch()
    {
        return $this->belongsTo(ClinicBranch::class, 'clinic_branch_id');
    }
}
