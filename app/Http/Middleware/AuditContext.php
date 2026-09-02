<?php

namespace App\Http\Middleware;

use App\Support\AuditContext as AuditContextStore;
use App\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditContext
{
    protected AuditContextStore $auditContext;
    protected RequestContext $requestContext;

    public function __construct(AuditContextStore $auditContext, RequestContext $requestContext)
    {
        $this->auditContext = $auditContext;
        $this->requestContext = $requestContext;
    }

    /**
     * Capture the client IP and user agent for the current request so
     * audit records produced later (including from observers/events)
     * attribute actions correctly.
     *
     * Prefers the centralized RequestContext (client_ip) when available
     * so IPS and audit share the same resolved IP.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $this->requestContext->clientIp() ?? $request->ip() ?? '0.0.0.0';
        $ua = $this->requestContext->userAgent() ?? $request->userAgent();

        $this->auditContext->set($ip, $ua);

        try {
            return $next($request);
        } finally {
            $this->auditContext->clear();
        }
    }
}
