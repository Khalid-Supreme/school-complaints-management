<?php

namespace App\Events;

use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Generic, explicit audit event.
 *
 * Carries everything needed to persist an audit record: the closed
 * action vocabulary, the acting user (nullable), the target resource
 * (polymorphic), a description, and a json-safe metadata payload.
 */
class AuditEvent
{
    use Dispatchable;

    public function __construct(
        public readonly AuditAction $action,
        public readonly ?User $user = null,
        public readonly ?Model $subject = null,
        public readonly ?string $description = null,
        public readonly array $metadata = [],
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null
    ) {}
}
