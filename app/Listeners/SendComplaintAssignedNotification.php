<?php

namespace App\Listeners;

use App\Events\ComplaintAssigned;
use App\Services\AesEncryptionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendComplaintAssignedNotification
{
    public function __construct(
        protected AesEncryptionService $encryption
    ) {}

    public function handle(ComplaintAssigned $event): void
    {
        $complaint = $event->complaint->fresh(['category']);
        $assignment = $event->assignment->fresh(['assignedTo.role', 'assignedBy']);

        if (! $complaint || ! $assignment) {
            return;
        }

        $officer = $assignment->assignedTo;

        if (! $officer || $officer->role?->slug !== 'complaint_officer') {
            return;
        }

        if (! $officer->is_active || ! $officer->email_verified_at) {
            return;
        }

        $recipientEmail = $officer->email;

        if (! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $title = $this->encryption->decrypt($complaint->title_encrypted) ?? 'Complaint';
        $category = $complaint->category?->name ?? 'Uncategorized';
        $status = $complaint->fresh()->status ?? $complaint->status;
        $assignedAt = $assignment->assigned_at?->toDateTimeString() ?? now()->toDateTimeString();
        $assignedBy = $assignment->assignedBy?->full_name ?? 'Admin';
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        try {
            Mail::send('emails.complaint-assigned', [
                'complaint' => $complaint,
                'title' => $title,
                'category' => $category,
                'assignedAt' => $assignedAt,
                'status' => $status,
                'assignedBy' => $assignedBy,
                'frontendUrl' => $frontendUrl,
            ], function ($message) use ($officer, $complaint) {
                $message->to($officer->email, $officer->full_name)
                    ->subject("Complaint Assigned to You — {$complaint->reference_no}");
            });
        } catch (\Throwable $e) {
            Log::warning('Complaint assigned notification failed', [
                'complaint_id' => $complaint->id,
                'officer_id' => $officer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
