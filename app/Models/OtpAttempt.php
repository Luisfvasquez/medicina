<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpAttempt extends Model
{
    use HasFactory;

    protected $table = 'otp_attempts';

    protected $fillable = [
        'identifier',
        'role',
        'attempts',
        'locked_until',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
    ];

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function recordFailure(): void
    {
        $max     = (int) config('otp.max_attempts', 3);
        $lockSec = (int) config('otp.lockout_seconds', 1800);

        $this->attempts = $this->attempts + 1;

        if ($this->attempts >= $max) {
            $this->locked_until = now()->addSeconds($lockSec);
        }

        $this->save();
    }

    public function recordSuccess(): void
    {
        $this->update([
            'attempts'    => 0,
            'locked_until' => null,
        ]);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeForIdentifier($query, string $identifier, string $role)
    {
        return $query->where('identifier', $identifier)
                     ->where('role', $role);
    }
}
