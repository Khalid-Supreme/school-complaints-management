<?php

use App\Http\Middleware\AuditContext;
use App\Http\Middleware\EnsureComplaintAccess;
use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\IpsMiddleware;
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
        // Restrict TRUSTED_PROXIES to known proxy IPs/CIDRs in production.
        $middleware->trustProxies(
            at: explode(',', (string) env('TRUSTED_PROXIES', '*')),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Register IPS Middleware globally for API requests
        $middleware->alias([
            'ips' => IpsMiddleware::class,
            'role' => EnsureRole::class,
            'complaint.access' => EnsureComplaintAccess::class,
            'email.verified' => EnsureEmailVerified::class,
            'audit.context' => AuditContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
