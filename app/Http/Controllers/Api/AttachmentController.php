<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AttachmentController extends Controller
{
    protected AttachmentService $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * Upload an attachment to a complaint.
     */
    public function store(Request $request, Complaint $complaint): JsonResponse
    {
        Gate::authorize('view', $complaint);

        $request->validate([
            'attachment' => 'required|file|max:10240', // 10MB limit
        ]);

        $attachment = $this->attachmentService->uploadAttachment($complaint, $request);

        return response()->json([
            'message' => 'File uploaded successfully',
            'data' => $attachment
        ], 201);
    }

    /**
     * List all attachments for a complaint.
     */
    public function index(Request $request, Complaint $complaint): JsonResponse
    {
        Gate::authorize('view', $complaint);

        $attachments = $this->attachmentService->getAttachments($complaint->id);

        return response()->json([
            'data' => $attachments
        ]);
    }
}
