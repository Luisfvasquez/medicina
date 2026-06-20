<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use \App\Traits\HasPublicUuid, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'user_id',
        'patient_account_id',
        'first_name',
        'last_name',
        'national_id',
        'birth_date',
        'gender',
        'email',
        'phone',
        'address',
        'city_id',
        'access_code',
        'last_login',
        'blood_type',
        'allergies',
        'chronic_conditions',
        'private_notes',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'datetime',
            'last_login' => 'datetime',
            'gender' => \App\Enums\Gender::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patientAccount()
    {
        return $this->belongsTo(PatientAccount::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
