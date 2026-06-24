<?php

namespace App\Models;

use App\Enums\DocType;
use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class MedicalDocument extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'patient_id',
        'patient_account_id',
        'clinic_branch_id',
        'type',
        'content',
        'public_token',
        'pending_upload',
        'file_path',
        'file_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'type'           => DocType::class,
            'pending_upload' => 'boolean',
            'file_size'      => 'integer',
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
