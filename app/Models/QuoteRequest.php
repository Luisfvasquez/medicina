<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteRequest extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'prescription_id',
        'patient_id',
        'city_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => \App\Enums\QuoteStatus::class,
        ];
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function offers()
    {
        return $this->hasMany(QuoteOffer::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
