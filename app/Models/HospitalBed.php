<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HospitalBed extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'hospitalization_room_id',
        'bed_number',
        'status',
        'daily_rate',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(HospitalizationRoom::class, 'hospitalization_room_id');
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(HospitalAdmission::class);
    }
}
