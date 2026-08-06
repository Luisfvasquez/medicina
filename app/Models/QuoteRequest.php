<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuoteRequest extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'prescription_id',
        'patient_id',
        'city_id',
        'status',
        'latitude',
        'longitude',
        'search_radius_km',
    ];

    protected function casts(): array
    {
        return [
            'status' => \App\Enums\QuoteStatus::class,
        ];
    }

    public function scopeForPharmacyLocation($query, $pharmacyLat, $pharmacyLng, $pharmacyCityId = null)
    {
        if (!$pharmacyLat || !$pharmacyLng) {
            // Fallback to city match if pharmacy has no coordinates
            if ($pharmacyCityId) {
                return $query->where('city_id', $pharmacyCityId);
            }
            return $query->whereRaw('1 = 0');
        }

        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

        return $query->selectRaw("quote_requests.*, {$haversine} AS distance", [$pharmacyLat, $pharmacyLng, $pharmacyLat])
                     ->where(function ($q) use ($haversine, $pharmacyLat, $pharmacyLng, $pharmacyCityId) {
                         $q->whereRaw("{$haversine} <= search_radius_km", [$pharmacyLat, $pharmacyLng, $pharmacyLat])
                           ->orWhereNull('latitude'); // Optional: fallback logic for old requests
                     });
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
