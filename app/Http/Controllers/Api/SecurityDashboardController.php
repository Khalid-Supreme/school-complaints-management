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
        // 1. Blocked IPs / Users from Cache lists (hybrid)
        $blockedIpsList = Cache::get('blocked_ips_list', []);
        $blockedIpsCount = count($blockedIpsList);
        $blockedUsersList = Cache::get('blocked_users_list', []);
        $blockedUsersCount = count($blockedUsersList);

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
                'blocked_users_count' => $blockedUsersCount,
            ],
            'recent_events' => array_reverse(array_slice($recentEvents, -20)), // show last 20 events, newest first
            'blocked_ips' => $blockedIpsList,
            'blocked_users' => $blockedUsersList,
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
     * Paginated list of currently blocked IPs (from cache).
     */
    public function blockedIps(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min(100, $perPage));
        $page = max(1, (int) $request->input('page', 1));
        $search = trim((string) $request->input('search', ''));

        $all = collect(Cache::get('blocked_ips_list', []));

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $all = $all->filter(function ($item) use ($needle) {
                return str_contains(mb_strtolower($item['ip'] ?? ''), $needle)
                    || str_contains(mb_strtolower($item['reason'] ?? ''), $needle)
                    || str_contains(mb_strtolower($item['user_email'] ?? ''), $needle);
            });
        }

        // Newest first
        $all = $all->reverse()->values();

        $total = $all->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $items = $all->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
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

    /**
     * Paginated list of currently blocked users (hybrid).
     */
    public function blockedUsers(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min(100, $perPage));
        $page = max(1, (int) $request->input('page', 1));
        $search = trim((string) $request->input('search', ''));

        $all = collect(Cache::get('blocked_users_list', []));

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $all = $all->filter(function ($item) use ($needle) {
                return str_contains(mb_strtolower($item['email'] ?? ''), $needle)
                    || str_contains(mb_strtolower($item['institution_id'] ?? ''), $needle)
                    || str_contains((string) ($item['user_id'] ?? ''), $needle)
                    || str_contains(mb_strtolower($item['reason'] ?? ''), $needle);
            });
        }

        $all = $all->reverse()->values();

        $total = $all->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $items = $all->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Unblock a user previously flagged by the hybrid IPS.
     */
    public function unblockUser(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $userId = (int) $validated['user_id'];

        Cache::forget('user_blocked_'.$userId);
        Cache::forget('rate_user_'.$userId);

        $blockedUsers = collect(Cache::get('blocked_users_list', []))
            ->reject(fn ($item) => (int) ($item['user_id'] ?? 0) === $userId)
            ->values()
            ->all();

        Cache::put('blocked_users_list', $blockedUsers, now()->addHours(24));

        $auditLogger->log(
            AuditAction::UserUnblocked,
            $request->user(),
            null,
            "User #{$userId} unblocked",
            ['user_id' => $userId]
        );

        return response()->json([
            'message' => "User #{$userId} has been unblocked.",
        ]);
    }
}
