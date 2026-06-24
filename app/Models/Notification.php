<?php

namespace App\Models;

use App\Enums\NotifType;
use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'patient_account_id',
        'type',
        'title',
        'message',
        'is_read',
        'link',
    ];

    protected function casts(): array
    {
        return [
            'type' => NotifType::class,
            'is_read' => 'boolean',
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
}
