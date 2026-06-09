<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\StoreComplaintRequest;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Services\ComplaintService;
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
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Complaint::class);
        
        $user = $request->user();
        
        if ($user->role->slug === 'complainant') {
            $complaints = $this->complaintService->getComplainantComplaints($user->id);
            return response()->json($complaints);
        }
        
        // For staff/admin, return empty for now until Assignment module is ready
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
                'reference_no' => $complaint->reference_no,
                'status' => $complaint->status,
            ]
        ], 201);
    }

    /**
     * Display the specified complaint.
     */
    public function show(Complaint $complaint): JsonResponse
    {
        Gate::authorize('view', $complaint);

        return response()->json([
            'data' => $this->complaintService->decryptComplaint($complaint)
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
}
