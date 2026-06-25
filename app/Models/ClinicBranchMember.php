<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicBranchMember extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'clinic_branch_id',
        'role',
        'department',
        'office_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'role' => \App\Enums\ClinicRole::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(ClinicBranch::class, 'clinic_branch_id');
    }
}
