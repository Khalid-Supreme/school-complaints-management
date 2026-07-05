<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\User;
use App\Services\ComplaintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    protected ComplaintService $complaintService;

    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;
    }

    /**
     * Provide metrics and recent activity for the system admin dashboard.
     */
    public function index(): JsonResponse
    {
        $totalComplaints = Complaint::count();
        $totalUsers = User::count();
        
        $assignedComplaints = Complaint::whereIn('status', ['assigned', 'in_progress'])->count();
            
        $securityEventsCount = Cache::get('security_event_count_sqli', 0) + 
                               Cache::get('security_event_count_xss', 0);

        // Recent complaints (last 5)
        $recentComplaints = Complaint::with(['complainant', 'category'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        $decryptedRecent = $recentComplaints->map(function ($c) {
            return $this->complaintService->decryptComplaint($c);
        });

        return response()->json([
            'total_complaints' => $totalComplaints,
            'total_users' => $totalUsers,
            'assigned_complaints' => $assignedComplaints,
            'security_events' => $securityEventsCount,
            'recent_complaints' => $decryptedRecent,
        ]);
    }
}
