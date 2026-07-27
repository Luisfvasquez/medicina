<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabAppointment extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'lab_quote_offer_id',
        'patient_id',
        'patient_account_id',
        'provider_profile_id',
        'scheduled_date',
        'time_slot',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
        ];
    }

    public function labQuoteOffer()
    {
        return $this->belongsTo(LabQuoteOffer::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function patientAccount()
    {
        return $this->belongsTo(PatientAccount::class);
    }

    public function providerProfile()
    {
        return $this->belongsTo(ProviderProfile::class);
    }
}
