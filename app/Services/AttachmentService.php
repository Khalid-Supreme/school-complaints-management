<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttachmentService
{
    /**
     * Disk used to persist complaint attachments. Set in config/uploads.php.
     * Must never resolve to a webroot-mounted disk.
     */
    protected string $disk;

    /**
     * Length limit of the original_filename column (string, 255 chars).
     */
    protected const MAX_FILENAME_LENGTH = 255;

    /**
     * Map of detected MIME type to the extension used on disk. The stored name
     * derives its extension from the file's detected MIME, never from the
     * client-supplied filename.
     */
    protected const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'text/plain' => 'txt',
    ];

    public function __construct()
    {
        $this->disk = (string) config('uploads.attachments.disk', 'local');
    }

    /**
     * Store a file attachment for a complaint.
     */
    public function uploadAttachment(Complaint $complaint, Request $request): ComplaintAttachment
    {
        $this->assertWithinExtraAttachmentLimit($complaint);

        $file = $request->file('attachment');

        $path = $file->storeAs(
            "complaints/{$complaint->id}/attachments",
            $this->randomStoredName($file->getMimeType()),
            $this->disk
        );

        return ComplaintAttachment::create([
            'complaint_id' => $complaint->id,
            'user_id' => Auth::id(),
            'file_path' => $path,
            'original_filename' => $this->sanitizeFilename($file->getClientOriginalName()),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }

    /**
     * A complainant may upload only a limited number of attachments after a
     * complaint has been submitted. The initial submission upload happens
     * immediately after submission, some attachments created within the grace
     * window of `submitted_at` are treated as part of the submission and do
     * not count against the limit.
     */
    protected function assertWithinExtraAttachmentLimit(Complaint $complaint): void
    {
        $limit = (int) config('complaints.extra_attachment_limit', 10);

        $graceMinutes = (int) config('complaints.extra_attachment_grace_minutes', 1);
        $submittedAt = $complaint->submitted_at?->copy() ?? $complaint->created_at;

        $extraCount = ComplaintAttachment::where('complaint_id', $complaint->id)
            ->where('created_at', '>', $submittedAt->addMinutes($graceMinutes))
            ->count();

        if ($extraCount >= $limit) {
            $label = $limit === 1 ? 'attachment' : 'attachments';

            throw ValidationException::withMessages([
                'attachment' => "You can upload only {$limit} additional {$label} after submission.",
            ]);
        }
    }

    /**
     * Build a cryptographically random stored filename. The extension comes
     * from the detected MIME type (the original client filename is trusted only
     * for display, never for the storage path).
     */
    protected function randomStoredName(?string $mimeType): string
    {
        $extension = self::MIME_EXTENSIONS[$mimeType] ?? 'bin';

        return Str::random(40).'.'.$extension;
    }

    /**
     * Strip control characters from a client-supplied filename and cap its
     * length so a hostile original name can never overflow the column or
     * smuggle header-injection characters into downloads.
     */
    protected function sanitizeFilename(string $filename): string
    {
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?? '';

        return mb_substr($clean, 0, self::MAX_FILENAME_LENGTH);
    }

    /**
     * Get all attachments for a complaint.
     */
    public function getAttachments(int $complaintId): Collection
    {
        return ComplaintAttachment::where('complaint_id', $complaintId)
            ->with(['user:id,first_name,last_name,title,role_id', 'user.role:id,slug'])
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
