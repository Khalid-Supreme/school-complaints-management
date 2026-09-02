<?php

namespace App\Http\Middleware;

use App\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestContextMiddleware
{
    protected RequestContext $requestContext;

    public function __construct(RequestContext $requestContext)
    {
        $this->requestContext = $requestContext;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->requestContext->capture($request);

        try {
            /** @var Response $response */
            $response = $next($request);

            // Echo Heroku request ID back for browser correlation (if present)
            if ($this->requestContext->requestId()) {
                $response->headers->set('X-Request-ID', $this->requestContext->requestId());
            }

            return $response;
        } finally {
            // Do not clear immediately if audit listeners run after response?
            // AuditEventListener runs synchronously during request, so clear is safe.
            // Keep until request lifecycle ends; using finally ensures cleanup.
            // We intentionally do NOT clear here for testing via afterResponse?
            // Clear after response is sent to avoid leaking to next request in Octane.
            $this->requestContext->clear();
        }
    }
}
