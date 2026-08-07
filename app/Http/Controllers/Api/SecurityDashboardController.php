<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use App\Models\Complaint;
use Illuminate\Http\JsonResponse;
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
                    ? round(($encryptedComplaints / $totalComplaints) * 100, 2) . '%' 
                    : '100%',
            ],
            'auth_health' => [
                'failed_logins_24h' => $failedLogins,
                'total_logins_24h' => $totalLogins,
                'failure_rate' => $failureRate . '%',
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
            'data' => LoginAttempt::with(['user:id,name,first_name,last_name,title,email,institution_id,role_id', 'user.role:id,slug'])
                ->orderBy('created_at', 'desc')
                ->paginate(50)
        ]);
    }
}
