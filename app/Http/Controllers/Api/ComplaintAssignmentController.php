<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assignment\AssignComplaintRequest;
use App\Models\Complaint;
use App\Models\User;
use App\Services\ComplaintAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplaintAssignmentController extends Controller
{
    protected ComplaintAssignmentService $assignmentService;

    public function __construct(ComplaintAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * Assign a complaint to a staff member (Admin only).
     */
    public function assign(AssignComplaintRequest $request, Complaint $complaint): JsonResponse
    {
        $user = $request->user();

        // Only admins can assign
        if ($user->role->slug !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $assignment = $this->assignmentService->assign(
            $complaint,
            $request->validated('assigned_to'),
            $user->id,
            $request->validated('note')
        );

        return response()->json([
            'message' => 'Complaint assigned successfully',
            'assignment' => [
                'id' => $assignment->id,
                'assigned_to' => $assignment->assignedTo->name,
                'assigned_by' => $assignment->assignedBy->name,
                'assigned_at' => $assignment->assigned_at,
            ],
        ]);
    }

    /**
     * Get assignments for the authenticated staff member.
     */
    public function myAssignments(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role->slug, ['staff', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $assignments = $this->assignmentService->getStaffAssignments($user->id);

        return response()->json($assignments);
    }

    /**
     * Get assignment history for a specific complaint.
     */
    public function history(Complaint $complaint): JsonResponse
    {
        $history = $this->assignmentService->getComplaintAssignmentHistory($complaint);

        return response()->json(['data' => $history]);
    }

    /**
     * Get list of staff members available for assignment.
     */
    public function staffList(): JsonResponse
    {
        $staff = User::whereHas('role', function ($query) {
            $query->where('slug', 'staff');
        })->where('is_active', true)->get(['id', 'name', 'email', 'department']);

        return response()->json(['data' => $staff]);
    }
}
