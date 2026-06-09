<?php

namespace App\Services;

use App\Models\ComplaintAttachment;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class AttachmentService
{
    protected string $disk = 'local';

    /**
     * Store a file attachment for a complaint.
     */
    public function uploadAttachment(Complaint $complaint, Request $request): ComplaintAttachment
    {
        $file = $request->file('attachment');
        
        // Generate a secure unique path
        $path = $file->store("complaints/{$complaint->id}/attachments", $this->disk);
        
        return ComplaintAttachment::create([
            'complaint_id' => $complaint->id,
            'user_id' => Auth::id(),
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }

    /**
     * Get all attachments for a complaint.
     */
    public function getAttachments(int $complaintId): \Illuminate\Database\Eloquent\Collection
    {
        return ComplaintAttachment::where('complaint_id', $complaintId)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Generate a secure temporary URL for an attachment.
     */
    public function getFileUrl(ComplaintAttachment $attachment): string
    {
        return Storage::disk($this->disk)->temporaryUrl(
            $attachment->file_path, 
            now()->addMinutes(15)
        );
    }
}
