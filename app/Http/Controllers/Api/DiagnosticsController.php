<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\RequestContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosticsController extends Controller
{
    /**
     * Admin-only diagnostic: shows how Laravel resolved the current request's
     * network identity vs raw headers. Useful for verifying TrustProxies on Heroku.
     */
    public function requestContext(Request $request, RequestContext $requestContext): JsonResponse
    {
        return response()->json([
            'resolved' => [
                'client_ip' => $requestContext->clientIp(),
                'description' => 'Laravel $request->ip() after TrustProxies — the IP IPS uses for blocking',
            ],
            'peer' => [
                'peer_ip' => $requestContext->peerIp(),
                'description' => 'Immediate TCP peer (REMOTE_ADDR) — on Heroku this is the router 10.x',
            ],
            'raw_headers' => [
                'x_forwarded_for' => $requestContext->xForwardedFor(),
                'x_real_ip' => $requestContext->xRealIp(),
                'forwarded' => $requestContext->forwarded(),
                'description' => 'Raw headers as received — untrusted unless proxy is trusted',
            ],
            'correlation' => [
                'request_id' => $requestContext->requestId(),
                'description' => 'Heroku X-Request-ID if present — use to correlate with Heroku router logs',
            ],
            'request' => [
                'method' => $requestContext->method(),
                'path' => $requestContext->path(),
                'route' => $requestContext->routeName(),
                'url' => $requestContext->url(),
                'host' => $requestContext->host(),
                'user_agent' => $requestContext->userAgent(),
            ],
            'proxy_config' => [
                'trusted_proxies' => $request->getTrustedProxies(),
                'trusted_header_set' => $request->getTrustedHeaderSet(),
                'note' => 'Empty trusted_proxies means X-Forwarded-For is ignored; Heroku needs TRUSTED_PROXIES set to see MTN public IP',
            ],
        ]);
    }
}
