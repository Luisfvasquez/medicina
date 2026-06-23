<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasPublicUuid;

    // HIPAA: No updated_at - logs are immutable
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'patient_id',
        'action',
        'resource',
        'resource_type',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'action' => \App\Enums\AuditAction::class,
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Log a VIEW action for HIPAA compliance
     */
    public static function logView(User $user, string $resourceType, string $resourceId, ?string $patientId = null, array $details = []): self
    {
        return self::create([
            'user_id' => $user->id,
            'patient_id' => $patientId,
            'action' => AuditAction::VIEW,
            'resource' => $resourceId,
            'resource_type' => $resourceType,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log a CREATE action
     */
    public static function logCreate(User $user, string $resourceType, string $resourceId, array $newData = [], ?string $patientId = null): self
    {
        return self::create([
            'user_id' => $user->id,
            'patient_id' => $patientId,
            'action' => AuditAction::CREATE,
            'resource' => $resourceId,
            'resource_type' => $resourceType,
            'details' => ['new' => $newData],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log an UPDATE action with old/new values
     */
    public static function logUpdate(User $user, string $resourceType, string $resourceId, array $oldData, array $newData, ?string $patientId = null): self
    {
        return self::create([
            'user_id' => $user->id,
            'patient_id' => $patientId,
            'action' => AuditAction::UPDATE,
            'resource' => $resourceId,
            'resource_type' => $resourceType,
            'details' => ['old' => $oldData, 'new' => $newData],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log a DELETE action
     */
    public static function logDelete(User $user, string $resourceType, string $resourceId, array $deletedData = [], ?string $patientId = null): self
    {
        return self::create([
            'user_id' => $user->id,
            'patient_id' => $patientId,
            'action' => AuditAction::DELETE,
            'resource' => $resourceId,
            'resource_type' => $resourceType,
            'details' => ['deleted' => $deletedData],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
