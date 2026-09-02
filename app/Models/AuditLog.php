<?php

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Immutable record of a security-sensitive action.
 *
 * Audit records are append-only through the application interface:
 *  - no updated_at column exists,
 *  - save()/update() on an existing model throws,
 *  - delete() always throws,
 *  - there are no API routes exposing mutation of this model.
 */
#[Fillable([
    'user_id',
    'action',
    'subject_type',
    'subject_id',
    'description',
    'metadata',
    'ip_address',
    'peer_ip',
    'request_id',
    'x_forwarded_for',
    'user_agent',
])]
class AuditLog extends Model
{
    use HasFactory;

    /**
     * The name of the "updated at" column.
     *
     * @var string|null
     */
    public const UPDATED_AT = null;

    /**
     * Resolve the human-readable action label.
     */
    public static function labelFor(AuditAction $action): string
    {
        return $action->label();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * The user who performed the action (null when unauthenticated).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The resource the action targeted (polymorphic).
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Enforce append-only semantics. Any attempt to modify or remove an
     * existing audit record throws instead of silently corrupting the log.
     */
    protected static function booted(): void
    {
        static::saving(function (AuditLog $log) {
            if ($log->exists) {
                throw new LogicException('Audit log records are immutable and cannot be updated.');
            }
        });

        static::deleting(function () {
            throw new LogicException('Audit log records are immutable and cannot be deleted.');
        });
    }
}
