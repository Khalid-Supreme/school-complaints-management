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

        // Perform intrusion detection scan (SQLi and XSS)
        $inputs = $request->all();
        $isSuspicious = false;
        $suspiciousReason = '';
        $detectedType = '';

        // Common SQL injection patterns
        $sqlPatterns = [
            '/union\s+select/i',
            '/select\s+.*\s+from/i',
            '/insert\s+into/i',
            '/update\s+.*\s+set/i',
            '/delete\s+from/i',
            '/drop\s+table/i',
            '/[\'"]\s*or\s*[\'"]?1[\'"]?\s*=\s*[\'"]?1/i',
            '/[\'"]\s*and\s*[\'"]?1[\'"]?\s*=\s*[\'"]?1/i',
            '/exec\s*\(/i',
            '/--/i',
        ];

        // Common XSS patterns
        $xssPatterns = [
            '/<script[^>]*>/i',
            '/<\/script>/i',
            '/javascript\s*:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/alert\s*\(/i',
            '/src\s*=\s*[\'"]?javascript/i',
            '/document\.cookie/i',
        ];

        foreach ($inputs as $key => $value) {
            if (is_string($value)) {
                // Check SQLi
                foreach ($sqlPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        $isSuspicious = true;
                        $suspiciousReason = "SQL Injection pattern matched in input '{$key}'";
                        $detectedType = 'sqli';
                        break 2;
                    }
                }

                // Check XSS
                foreach ($xssPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        $isSuspicious = true;
                        $suspiciousReason = "Cross-Site Scripting (XSS) pattern matched in input '{$key}'";
                        $detectedType = 'xss';
                        break 2;
                    }
                }
            }
        }

        if ($isSuspicious) {
            // Log the security event in cache for the dashboard
            $this->recordSecurityEvent($detectedType, $ip, $request, $suspiciousReason);

            // Block IP immediately
            Cache::put($cacheKey, true, now()->addHours(24));

            // Log to blocked IP list in Cache
            $blockedIps = Cache::get('blocked_ips_list', []);
            $exists = false;
            foreach ($blockedIps as $item) {
                if ($item['ip'] === $ip) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $blockedIps[] = [
                    'ip' => $ip,
                    'reason' => $suspiciousReason,
                    'blocked_at' => now()->toIso8601String(),
                ];
                Cache::put('blocked_ips_list', $blockedIps, now()->addHours(24));
            }

            return response()->json([
                'error' => 'Security policy violation. Suspicious activity has been detected and logged.',
                'code' => 'INTRUSION_DETECTED'
            ], 403);
        }

        // Log the rate limit attempt
        $attempts = Cache::get($attemptKey, 0);
        Cache::put($attemptKey, $attempts + 1, now()->addHours(1));

        // Define thresholds for blocking
        $threshold = 100; // Max requests per hour per IP
        
        if ($attempts >= $threshold) {
            Cache::put($cacheKey, true, now()->addHours(24)); // Block for 24 hours

            // Log rate limit block
            $blockedIps = Cache::get('blocked_ips_list', []);
            $exists = false;
            foreach ($blockedIps as $item) {
                if ($item['ip'] === $ip) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $blockedIps[] = [
                    'ip' => $ip,
                    'reason' => 'Rate limit exceeded (' . $attempts . ' requests/hr)',
                    'blocked_at' => now()->toIso8601String(),
                ];
                Cache::put('blocked_ips_list', $blockedIps, now()->addHours(24));
            }

            return response()->json([
                'error' => 'Too many requests. Your IP has been blocked.',
                'code' => 'IP_BLOCKED'
            ], 429);
        }

        return $next($request);
    }

    protected function recordSecurityEvent($type, $ip, Request $request, $reason)
    {
        $events = Cache::get('security_events', []);
        $events[] = [
            'type' => $type, // 'sqli' or 'xss'
            'ip' => $ip,
            'reason' => $reason,
            'url' => $request->fullUrl(),
            'payload' => json_encode($request->except(['password'])), // don't log passwords
            'created_at' => now()->toIso8601String(),
        ];

        // Keep last 500 events
        if (count($events) > 2000) {
            array_shift($events);
        }
        Cache::put('security_events', $events, now()->addDays(7));
        Cache::increment("security_event_count_{$type}");
    }
}
