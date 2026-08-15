<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\StoreComplaintRequest;
use App\Http\Requests\Complaint\UpdateComplaintStatusRequest;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Services\ComplaintAssignmentService;
use App\Services\ComplaintService;
use App\Services\ComplaintWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ComplaintController extends Controller
{
    protected ComplaintService $complaintService;

    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;
    }

    /**
     * Display a listing of complaints for the authenticated user.
     * Complainants (student/staff) see their own; admin sees all;
     * complaint_officer sees their assignments.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Complaint::class);

        $user = $request->user();
        $slug = $user->role->slug;

        // Students / Staff complainants see only their own complaints
        if (in_array($slug, ['student', 'staff'], true)) {
            $complaints = $this->complaintService->getComplainantComplaints($user->id);

            return response()->json($complaints);
        }

        // Admins and Sub-Admins see everything (paginated, decrypted)
        if (in_array($slug, ['admin', 'sub_admin'], true)) {
            $query = Complaint::with(['complainant.role', 'category', 'currentAssignment.assignedTo.role']);

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            $paginator = $query->orderBy('created_at', 'desc')->paginate(15);

            $decryptedItems = collect($paginator->items())->map(function ($complaint) {
                return $this->complaintService->decryptComplaint($complaint);
            });

            return response()->json([
                'data' => $decryptedItems,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        }

        // Complaint officers see their assigned complaints
        if ($slug === 'complaint_officer') {
            $assignments = app(ComplaintAssignmentService::class)->getStaffAssignments($user->id);

            return response()->json($assignments);
        }

        return response()->json(['data' => [], 'meta' => []]);
    }

    /**
     * Store a newly created complaint in storage.
     */
    public function store(StoreComplaintRequest $request): JsonResponse
    {
        Gate::authorize('create', Complaint::class);

        $complaint = $this->complaintService->submitComplaint(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'message' => 'Complaint submitted successfully',
            'complaint' => [
                'id' => $complaint->id,
                'reference_no' => $complaint->reference_no,
                'status' => $complaint->status,
            ],
        ], 201);
    }

    /**
     * Display the specified complaint.
     */
    public function show(Complaint $complaint): JsonResponse
    {
        Gate::authorize('view', $complaint);

        return response()->json([
            'data' => $this->complaintService->decryptComplaint($complaint),
        ]);
    }

    /**
     * Get all active complaint categories.
     */
    public function categories(): JsonResponse
    {
        $categories = ComplaintCategory::where('is_active', true)->get();

        return response()->json(['data' => $categories]);
    }

    /**
     * Update the status of a complaint.
     * Authorization is enforced by route middleware (role + complaint.access)
     * and by the ComplaintPolicy::update ability.
     */
    public function updateStatus(UpdateComplaintStatusRequest $request, Complaint $complaint): JsonResponse
    {
        Gate::authorize('update', $complaint);

        $workflow = app(ComplaintWorkflowService::class);
        $workflow->transitionStatus($complaint, $request->validated('status'));

        return response()->json([
            'message' => 'Complaint status updated successfully',
            'status' => $complaint->status,
        ]);
    }
}
