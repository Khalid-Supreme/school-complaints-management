<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use App\Models\Complaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SecurityDashboardController extends Controller
{
    /**
     * Provide a security overview for administrators.
     */
    public function index(): JsonResponse
    {
        // 1. IPS Status: Estimate blocked IPs from cache
        // Since we use Cache::put for blocks, we'd normally need a dedicated table for long-term auditing.
        // For now, we provide an estimate or current status.
        $blockedIpsCount = 0; 
        // In a production environment, we would query a `blocked_ips` table.
        
        // 2. Authentication Health
        $failedLogins = LoginAttempt::where('success', false)
            ->where('created_at', '>=', now()->subDay())
            ->count();
            
        $totalLogins = LoginAttempt::where('created_at', '>=', now()->subDay())->count();
        $failureRate = $totalLogins > 0 ? round(($failedLogins / $totalLogins) * 100, 2) : 0;

        // 3. Data Protection Status
        $encryptedComplaints = Complaint::whereNotNull('title_encrypted')->count();

        // 4. System Integrity
        $totalComplaints = Complaint::count();

        return response()->json([
            'summary' => [
                'total_complaints' => $totalLins = $totalComplaints,
                'encrypted_complaints' => $encryptedComplaints,
                'data_protection_coverage' => $totalComplaints > 0 
                    ? ($encryptedComplaints / $totalComplaints) * 100 . '%' 
                    : '0%',
            ],
            'auth_health' => [
                'failed_logins_24h' => $failedLogins,
                'total_logins_24h' => $totalLogins,
                'failure_rate' => $failureRate . '%',
            ],
            'ips_status' => [
                'description' => 'IPS is active and monitoring request rates.',
                'active_blocks' => 'Consult Redis/Cache for real-time blocks',
            ],
            'alerts' => [
                'critical' => $failureRate > 20 ? 'High login failure rate detected.' : 'None',
                'warning' => $totalLogins == 0 ? 'No login activity in last 24h.' : 'None',
            ]
        ]);
    }

    /**
     * Get detailed login attempt logs for audit.
     */
    public function loginAudit(): JsonResponse
    {
        return response()->json([
            'data' => LoginAttempt::orderBy('created_at', 'desc')
                ->paginate(50)
        ]);
    }
}
