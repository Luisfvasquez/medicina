<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabRequest extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'consultation_id',
        'exams_list',
        'instructions',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'exams_list' => 'array',
            'is_completed' => 'boolean',
        ];
    }

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
}
