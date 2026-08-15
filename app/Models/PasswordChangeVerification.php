<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One-time email verification challenge for an administrator-initiated or
 * voluntary password change. The challenge is single-use, expires, and only
 * ever stores a bcrypt hash of the 6-digit code — never the code itself.
 */
#[Fillable([
    'user_id',
    'code_hash',
    'expires_at',
    'attempts',
    'max_attempts',
    'used_at',
])]
class PasswordChangeVerification extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUsed(): bool
    {
        return ! is_null($this->used_at);
    }

    public function isExpired(): bool
    {
        if ($this->isUsed()) {
            return false;
        }

        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function attemptsRemaining(): int
    {
        return max(0, (int) $this->max_attempts - (int) $this->attempts);
    }

    public function consumeAttempt(): void
    {
        $this->attempts = $this->attempts + 1;
        $this->save();
    }
}
