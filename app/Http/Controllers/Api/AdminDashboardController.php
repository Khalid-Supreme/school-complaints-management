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
        $totalUsers = User::count();
        $totalStudents = User::whereHas('role', fn($q) => $q->where('slug', 'student'))->count();
        $totalStaff = User::whereHas('role', fn($q) => $q->whereIn('slug', ['staff', 'complaint_officer', 'sub_admin']))
            ->count();
        $totalComplaintOfficers = User::whereHas('role', fn($q) => $q->where('slug', 'complaint_officer'))->count();
        $totalComplaints = Complaint::count();
        $pendingComplaints = Complaint::where('status', 'submitted')->count();
        $resolvedComplaints = Complaint::where('status', 'resolved')->count();
        $securityEventsCount = Cache::get('security_event_count_sqli', 0) + 
                               Cache::get('security_event_count_xss', 0);

        $months = collect(range(11, 0))->map(fn($i) => now()->subMonths($i)->format('Y-m'));

        $complaintsByMonth = Complaint::selectRaw("to_char(created_at, 'YYYY-MM') as month, count(*) as total")
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $resolvedByMonth = Complaint::selectRaw("to_char(resolved_at, 'YYYY-MM') as month, count(*) as total")
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $chartLabels = $months->toArray();
        $chartComplaints = $months->map(fn($m) => $complaintsByMonth->get($m, 0))->values()->toArray();
        $chartResolved = $months->map(fn($m) => $resolvedByMonth->get($m, 0))->values()->toArray();

        // Recent complaints (last 5)
        $recentComplaints = Complaint::with(['complainant', 'category'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $decryptedRecent = $recentComplaints->map(function ($c) {
            return $this->complaintService->decryptComplaint($c);
        });

        return response()->json([
            'total_users' => $totalUsers,
            'total_students' => $totalStudents,
            'total_staff' => $totalStaff,
            'total_complaint_officers' => $totalComplaintOfficers,
            'total_complaints' => $totalComplaints,
            'pending_complaints' => $pendingComplaints,
            'resolved_complaints' => $resolvedComplaints,
            'security_events' => $securityEventsCount,
            'chart_data' => [
                'labels' => $chartLabels,
                'complaints' => $chartComplaints,
                'resolved' => $chartResolved,
            ],
            'recent_complaints' => $decryptedRecent,
        ]);
    }
}
