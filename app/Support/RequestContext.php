<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Centralized request-network context.
 *
 * Captures everything needed for forensic IPS/audit correlation without
 * duplicating $request->ip() logic across middleware/listeners.
 *
 * Field semantics (documented for audit clarity):
 *
 * - client_ip:        Resolved client IP via $request->ip() (Symfony trusted-proxy aware).
 *                     This is the IP the IPS uses for blocking decisions.
 * - peer_ip:          Immediate network peer as seen by PHP (REMOTE_ADDR).
 *                     On Heroku this is the router's 10.x address.
 * - x_forwarded_for:  Raw X-Forwarded-For header (untrusted metadata).
 * - x_real_ip:        Raw X-Real-IP header (untrusted metadata).
 * - forwarded:        Raw Forwarded header (untrusted metadata).
 * - request_id:       Heroku/Laravel X-Request-ID header if present (correlation ID).
 * - user_agent:       HTTP User-Agent.
 * - method:           HTTP method (GET, POST...).
 * - path:             Request path without query (e.g. /api/complaints).
 * - route:            Laravel route name (e.g. complaints.store) if resolved.
 * - url:              Full URL.
 * - host:             HTTP Host.
 */
class RequestContext
{
    protected ?string $clientIp = null;
    protected ?string $peerIp = null;
    protected ?string $xForwardedFor = null;
    protected ?string $xRealIp = null;
    protected ?string $forwarded = null;
    protected ?string $requestId = null;
    protected ?string $userAgent = null;
    protected ?string $method = null;
    protected ?string $path = null;
    protected ?string $routeName = null;
    protected ?string $url = null;
    protected ?string $host = null;

    /**
     * Populate from the current HTTP request.
     * Call once per request, early in the middleware stack.
     */
    public function capture(Request $request): void
    {
        // Trusted/resolved client IP — Symfony applies TrustProxies config.
        $this->clientIp = $request->ip() ?? '0.0.0.0';

        // Immediate peer — the TCP peer as observed by PHP-FPM/nginx.
        $this->peerIp = $request->server('REMOTE_ADDR');

        // Raw forwarded headers — observational only, never trusted for identity.
        $this->xForwardedFor = $request->header('X-Forwarded-For');
        $this->xRealIp = $request->header('X-Real-IP');
        $this->forwarded = $request->header('Forwarded');

        // Heroku sets X-Request-ID on every request via its router.
        // Laravel may also set it; header() is case-insensitive.
        $this->requestId = $request->header('X-Request-ID') ?? $request->header('X-Request-Id');

        $this->userAgent = $request->userAgent();
        $this->method = $request->method();
        $this->path = $request->path() !== '' ? '/'.$request->path() : '/';
        $this->url = $request->fullUrl();
        $this->host = $request->getHost();

        try {
            $this->routeName = $request->route()?->getName();
        } catch (\Throwable $e) {
            $this->routeName = null;
        }
    }

    public function clear(): void
    {
        $this->clientIp = null;
        $this->peerIp = null;
        $this->xForwardedFor = null;
        $this->xRealIp = null;
        $this->forwarded = null;
        $this->requestId = null;
        $this->userAgent = null;
        $this->method = null;
        $this->path = null;
        $this->routeName = null;
        $this->url = null;
        $this->host = null;
    }

    public function clientIp(): ?string { return $this->clientIp; }
    public function peerIp(): ?string { return $this->peerIp; }
    public function xForwardedFor(): ?string { return $this->xForwardedFor; }
    public function xRealIp(): ?string { return $this->xRealIp; }
    public function forwarded(): ?string { return $this->forwarded; }
    public function requestId(): ?string { return $this->requestId; }
    public function userAgent(): ?string { return $this->userAgent; }
    public function method(): ?string { return $this->method; }
    public function path(): ?string { return $this->path; }
    public function routeName(): ?string { return $this->routeName; }
    public function url(): ?string { return $this->url; }
    public function host(): ?string { return $this->host; }

    /**
     * Structured array for audit metadata.
     * Only includes non-empty values; passwords/tokens are never included
     * (caller must not add them).
     */
    public function toArray(): array
    {
        return array_filter([
            'client_ip' => $this->clientIp,
            'peer_ip' => $this->peerIp,
            'x_forwarded_for' => $this->xForwardedFor,
            'x_real_ip' => $this->xRealIp,
            'forwarded' => $this->forwarded,
            'request_id' => $this->requestId,
            'user_agent' => $this->userAgent,
            'method' => $this->method,
            'path' => $this->path,
            'route' => $this->routeName,
            'url' => $this->url,
            'host' => $this->host,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Array specifically for IPS decision logging — makes the blocking IP explicit.
     */
    public function forSecurityDecision(string $decisionIp): array
    {
        $base = $this->toArray();
        $base['ip_used_for_security_decision'] = $decisionIp;
        // Also expose blocked_ip for symmetry on ip.blocked events
        $base['blocked_ip'] = $decisionIp;
        return $base;
    }
}
