<?php

namespace App\Models;

use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtpCode extends Model
{
    use HasFactory, HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'identifier',
        'channel',
        'role',
        'code_hash',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Relación polimórfica al usuario o paciente associated.
     * Se resuelve dinámicamente según el rol.
     */
    public function userable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereNull('verified_at')
                     ->where('expires_at', '>', now());
    }

    public function scopeForIdentifier($query, string $identifier, string $role)
    {
        return $query->where('identifier', $identifier)
                     ->where('role', $role);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Verifica el código plano contra el hash almacenado.
     */
    public function verify(string $plainCode): bool
    {
        return hash_equals($this->code_hash, hash('sha256', $plainCode));
    }

    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }
}
