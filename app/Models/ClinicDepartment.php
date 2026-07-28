<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicDepartment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'clinic_branch_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ClinicBranch::class, 'clinic_branch_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(ClinicStaff::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ClinicService::class);
    }
}
