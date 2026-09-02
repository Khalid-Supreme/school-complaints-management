<?php

use App\Http\Middleware\AuditContext;
use App\Http\Middleware\EnsureComplaintAccess;
use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\IpsMiddleware;
use App\Http\Middleware\RequestContextMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // Trust the configured proxies so $request->ip() resolves the real
        // client address behind Heroku/nginx/Cloudflare load balancers.
        // IMPORTANT: do NOT use '*' — it trusts the X-Forwarded-For header  
        // from ANY client, letting remote users spoof their IP (which also
        // breaks the per-IP IPS block). Leave TRUSTED_PROXIES unset when the
        // app is reached directly, and list only real proxy IPs/CIDRs when
        // it is served behind nginx/Cloudflare.
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES') ? explode(',', (string) env('TRUSTED_PROXIES')) : [],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Distributed request context (client_ip, peer_ip, xff, request_id) — must run first
        $middleware->api(prepend: [
            RequestContextMiddleware::class,
        ]);

        // Register IPS Middleware globally for API requests
        $middleware->alias([
            'ips' => IpsMiddleware::class,
            'role' => EnsureRole::class,
            'complaint.access' => EnsureComplaintAccess::class,
            'email.verified' => EnsureEmailVerified::class,
            'password.changed' => EnsurePasswordChanged::class,
            'audit.context' => AuditContext::class,
            'request.context' => RequestContextMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
 