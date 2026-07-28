<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicStaff extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'clinic_branch_id',
        'user_id',
        'clinic_role_id',
        'clinic_department_id',
        'is_active',
        'license_number',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ClinicBranch::class, 'clinic_branch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ClinicRole::class, 'clinic_role_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(ClinicDepartment::class, 'clinic_department_id');
    }

    public function assignedServiceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'assigned_staff_id');
    }
}
