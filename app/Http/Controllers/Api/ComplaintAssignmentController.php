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
     * Assign a complaint to a complaint officer.
     * Authorization is enforced by route middleware (role:admin).
     */
    public function assign(AssignComplaintRequest $request, Complaint $complaint): JsonResponse
    {
        $assignment = $this->assignmentService->assign(
            $complaint,
            $request->validated('assigned_to'),
            $request->user()->id,
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
     * Get assignments for the authenticated complaint officer.
     * Authorization is enforced by route middleware (role:complaint_officer).
     */
    public function myAssignments(Request $request): JsonResponse
    {
        $assignments = $this->assignmentService->getStaffAssignments($request->user()->id);

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
            $query->where('slug', 'complaint_officer');
        })->where('is_active', true)->get(['id','institution_id', 'name', 'email', 'department']);

        return response()->json(['data' => $staff]);
    }
}
