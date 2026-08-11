<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Events\AuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Convenience facade over the audit event stream.
 *
 * Centralizes dispatch so call-sites remain one-liners and never need to
 * know about the underlying event/listener wiring.
 */
class AuditLogger
{
    /**
     * Record a security-sensitive action.
     *
     * @param  array<string, mixed>  $metadata  JSON-safe context (IDs, status
     *                                          changes, field names, counts).
     *                                          Never include passwords, tokens
     *                                          or decrypted complaint content.
     */
    public function log(
        AuditAction $action,
        ?User $user = null,
        ?Model $subject = null,
        ?string $description = null,
        array $metadata = []
    ): void {
        AuditEvent::dispatch($action, $user, $subject, $description, $metadata);
    }
}
