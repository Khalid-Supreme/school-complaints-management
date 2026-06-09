<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IpsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $cacheKey = 'ips_blocked_' . $ip;
        $attemptKey = 'ips_attempts_' . $ip;

        // Check if IP is already blocked
        if (Cache::has($cacheKey)) {
            return response()->json([
                'error' => 'Your IP address has been temporarily blocked due to suspicious activity.',
                'code' => 'IP_BLOCKED'
            ], 429);
        }

        // Log the attempt
        $attempts = Cache::get($attemptKey, 0);
        Cache::put($attemptKey, $attempts + 1, now()->addHours(1));

        // Define thresholds for blocking
        $threshold = 100; // Max requests per hour per IP
        
        if ($attempts >= $threshold) {
            Cache::put($cacheKey, true, now()->addHours(24)); // Block for 24 hours
            return response()->json([
                'error' => 'Too many requests. Your IP has been blocked.',
                'code' => 'IP_BLOCKED'
            ], 429);
        }

        return $next($request);
    }
}
