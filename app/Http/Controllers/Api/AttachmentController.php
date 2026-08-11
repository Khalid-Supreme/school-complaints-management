<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attachment\StoreAttachmentRequest;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

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
    public function store(StoreAttachmentRequest $request, Complaint $complaint): JsonResponse
    {
        Gate::authorize('view', $complaint);

        $attachment = $this->attachmentService->uploadAttachment($complaint, $request);

        return response()->json([
            'message' => 'File uploaded successfully',
            'data' => $attachment,
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
            'data' => $attachments,
        ]);
    }

    /**
     * Securely download a complaint attachment.
     */
    public function download(Complaint $complaint, ComplaintAttachment $attachment)
    {
        Gate::authorize('view', $complaint);

        if ($attachment->complaint_id !== $complaint->id) {
            abort(404);
        }

        $path = Storage::disk(config('uploads.attachments.disk', 'local'))->path($attachment->file_path);

        return response()->download($path, $attachment->original_filename);
    }
}
