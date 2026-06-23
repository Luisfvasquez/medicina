<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabResult extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'lab_request_id',
        'patient_id',
        'file_url',
        'result_json',
        'notes',
        'reviewed_by',
        'reviewed_at',
        'status',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'result_json' => 'array',
            'reviewed_at' => 'datetime',
            'performed_at' => 'datetime',
            'status' => \App\Enums\LabResultStatus::class,
        ];
    }

    public function labRequest()
    {
        return $this->belongsTo(LabRequest::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
