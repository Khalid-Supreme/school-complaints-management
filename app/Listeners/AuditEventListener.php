<?php

namespace App\Listeners;

use App\Events\AuditEvent;
use App\Models\AuditLog;
use App\Support\AuditContext as AuditContextStore;
use App\Support\RequestContext;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditEventListener
{
    protected AuditContextStore $auditContext;
    protected RequestContext $requestContext;

    public function __construct(AuditContextStore $auditContext, RequestContext $requestContext)
    {
        $this->auditContext = $auditContext;
        $this->requestContext = $requestContext;
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
        // Prefer centralized RequestContext (client_ip) over legacy AuditContext
        // so IPS and audit share the same resolved IP.
        $ipAddress = $event->ipAddress
            ?? $this->requestContext->clientIp()
            ?? $this->auditContext->ipAddress()
            ?? '0.0.0.0';
        $userAgent = $event->userAgent
            ?? $this->requestContext->userAgent()
            ?? $this->auditContext->userAgent();

        // Network correlation fields — stored as dedicated columns for filtering,
        // with raw forwarded headers kept as metadata (untrusted).
        $peerIp = $this->requestContext->peerIp();
        $requestId = $this->requestContext->requestId();
        $xForwardedFor = $this->requestContext->xForwardedFor();

        // Enrich metadata with structured network context (filtered, no secrets).
        // Existing metadata from caller is preserved; request context is merged
        // with IPS decision fields taking precedence if already present.
        $contextMeta = array_filter([
            'client_ip' => $this->requestContext->clientIp(),
            'peer_ip' => $peerIp,
            'x_forwarded_for' => $xForwardedFor,
            'x_real_ip' => $this->requestContext->xRealIp(),
            'forwarded' => $this->requestContext->forwarded(),
            'request_id' => $requestId,
            'method' => $this->requestContext->method(),
            'path' => $this->requestContext->path(),
            'route' => $this->requestContext->routeName(),
            'host' => $this->requestContext->host(),
        ], fn ($v) => $v !== null && $v !== '');

        // Merge caller metadata (e.g. detected_type, ip_used_for_security_decision)
        // with request context — caller wins on collision to preserve explicit IPS fields.
        $mergedMetadata = array_merge($contextMeta, $event->metadata);
        // Remove nulls that could have been merged
        $mergedMetadata = array_filter($mergedMetadata, fn ($v) => $v !== null && $v !== '');

        try {
            AuditLog::create([
                'user_id' => $event->user?->id,
                'action' => $event->action->value,
                'subject_type' => $event->subject ? $event->subject->getMorphClass() : null,
                'subject_id' => $event->subject?->getKey(),
                'description' => $event->description,
                'metadata' => $mergedMetadata !== [] ? $mergedMetadata : null,
                'ip_address' => $ipAddress,
                'peer_ip' => $peerIp,
                'request_id' => $requestId,
                'x_forwarded_for' => $xForwardedFor ? mb_substr($xForwardedFor, 0, 1000) : null,
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
