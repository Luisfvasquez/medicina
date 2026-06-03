<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'is_read',
        'link',
    ];

    protected function casts(): array
    {
        return [
            'type' => \App\Enums\NotifType::class,
            'is_read' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
