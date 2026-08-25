<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\LoginAttempt;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SecurityDashboardController extends Controller
{
    /**
     * Provide a security overview for administrators and security analysts.
     */
    public function index(): JsonResponse
    {
        // 1. Blocked IPs from Cache list
        $blockedIpsList = Cache::get('blocked_ips_list', []);
        $blockedIpsCount = count($blockedIpsList);

        // 2. Authentication Health (Login Attempts)
        $failedLogins = LoginAttempt::where('successful', false)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $totalLogins = LoginAttempt::where('created_at', '>=', now()->subDay())->count();
        $failureRate = $totalLogins > 0 ? round(($failedLogins / $totalLogins) * 100, 2) : 0;

        // 3. Security Detections from Cache
        $sqliCount = Cache::get('security_event_count_sqli', 0);
        $xssCount = Cache::get('security_event_count_xss', 0);
        $recentEvents = Cache::get('security_events', []);

        // 4. Data Protection Status
        $encryptedComplaints = Complaint::whereNotNull('title_encrypted')->count();
        $totalComplaints = Complaint::count();

        return response()->json([
            'summary' => [
                'total_complaints' => $totalComplaints,
                'encrypted_complaints' => $encryptedComplaints,
                'data_protection_coverage' => $totalComplaints > 0
                    ? round(($encryptedComplaints / $totalComplaints) * 100, 2).'%'
                    : '100%',
            ],
            'auth_health' => [
                'failed_logins_24h' => $failedLogins,
                'total_logins_24h' => $totalLogins,
                'failure_rate' => $failureRate.'%',
            ],
            'intrusion_detections' => [
                'sqli_count' => $sqliCount,
                'xss_count' => $xssCount,
                'blocked_ips_count' => $blockedIpsCount,
            ],
            'recent_events' => array_reverse(array_slice($recentEvents, -20)), // show last 20 events, newest first
            'blocked_ips' => $blockedIpsList,
        ]);
    }

    /**
     * Get detailed login attempt logs for audit.
     */
    public function loginAudit(): JsonResponse
    {
        return response()->json([
            'data' => LoginAttempt::with(['user:id,first_name,last_name,title,email,institution_id,role_id', 'user.role:id,slug'])
                ->orderBy('created_at', 'desc')
                ->paginate(50),
        ]);
    }

    /**
     * Unblock an IP address previously flagged by the IPS, without wiping
     * the whole cache. The block keys, the dashboard list, and the attempt
     * counter for that IP are cleared, and the action is audit-logged.
     */
    public function unblockIp(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $validated = $request->validate([
            'ip' => ['required', 'ip'],
        ]);

        $ip = $validated['ip'];

        Cache::forget('ips_blocked_'.$ip);
        Cache::forget('ips_attempts_'.$ip);

        $blockedIps = collect(Cache::get('blocked_ips_list', []))
            ->reject(fn ($item) => ($item['ip'] ?? null) === $ip)
            ->values()
            ->all();

        Cache::put('blocked_ips_list', $blockedIps, now()->addHours(24));

        $auditLogger->log(
            AuditAction::IpUnblocked,
            $request->user(),
            null,
            "IP address {$ip} unblocked",
            ['ip' => $ip]
        );

        return response()->json([
            'message' => "IP {$ip} has been unblocked.",
        ]);
    }
}
