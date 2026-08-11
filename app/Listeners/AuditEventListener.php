<?php

namespace App\Listeners;

use App\Events\AuditEvent;
use App\Models\AuditLog;
use App\Support\AuditContext as AuditContextStore;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditEventListener
{
    protected AuditContextStore $auditContext;

    public function __construct(AuditContextStore $auditContext)
    {
        $this->auditContext = $auditContext;
    }

    /**
     * Persist an audit event as an immutable audit_logs row.
     *
     * The audit trail is a side concern: a write failure must never take
     * down the request it is recording (login, password reset, ...). If the
     * record cannot be stored we log the failure and continue.
     */
    public function handle(AuditEvent $event): void
    {
        // Fall back to the request-scoped context when the event does not
        // carry explicit network values (e.g. events fired from observers).
        $ipAddress = $event->ipAddress ?? $this->auditContext->ipAddress() ?? '0.0.0.0';
        $userAgent = $event->userAgent ?? $this->auditContext->userAgent();

        try {
            AuditLog::create([
                'user_id' => $event->user?->id,
                'action' => $event->action->value,
                'subject_type' => $event->subject ? $event->subject->getMorphClass() : null,
                'subject_id' => $event->subject?->getKey(),
                'description' => $event->description,
                'metadata' => $event->metadata !== [] ? $event->metadata : null,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        } catch (Throwable $throwable) {
            Log::warning('Failed to persist audit log record.', [
                'action' => $event->action->value,
                'subject' => $event->subject ? [$event->subject->getMorphClass(), $event->subject->getKey()] : null,
                'error' => $throwable->getMessage(),
            ]);
        }
    }
}
