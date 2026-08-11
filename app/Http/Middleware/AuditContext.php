<?php

namespace App\Http\Middleware;

use App\Support\AuditContext as AuditContextStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditContext
{
    protected AuditContextStore $auditContext;

    public function __construct(AuditContextStore $auditContext)
    {
        $this->auditContext = $auditContext;
    }

    /**
     * Capture the client IP and user agent for the current request so
     * audit records produced later (including from observers/events)
     * attribute actions correctly.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->auditContext->set($request->ip() ?? '0.0.0.0', $request->userAgent());

        try {
            return $next($request);
        } finally {
            $this->auditContext->clear();
        }
    }
}
