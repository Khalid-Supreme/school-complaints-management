<?php

namespace App\Listeners;

use App\Events\ComplaintStatusChanged;
use App\Services\AesEncryptionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendComplaintStatusChangedNotification
{
    public function __construct(
        protected AesEncryptionService $encryption
    ) {}

    public function handle(ComplaintStatusChanged $event): void
    {
        if ($event->oldStatus === $event->newStatus) {
            return;
        }

        $complaint = $event->complaint->fresh(['category', 'complainant.role']);

        if (! $complaint || ! $complaint->complainant) {
            return;
        }

        $complainant = $complaint->complainant;

        if (! $complainant->is_active || ! $complainant->email_verified_at) {
            return;
        }

        $recipientEmail = $complainant->email;

        if (! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $title = $this->encryption->decrypt($complaint->title_encrypted) ?? 'Complaint';
        $changedAt = $complaint->updated_at?->toDateTimeString() ?? now()->toDateTimeString();
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        try {
            Mail::send('emails.complaint-status-changed', [
                'complaint' => $complaint,
                'complainant' => $complainant,
                'title' => $title,
                'oldStatus' => $event->oldStatus,
                'newStatus' => $event->newStatus,
                'changedAt' => $changedAt,
                'frontendUrl' => $frontendUrl,
            ], function ($message) use ($complainant, $complaint) {
                $message->to($complainant->email, $complainant->full_name)
                    ->subject("Complaint Status Updated — {$complaint->reference_no}");
            });
        } catch (\Throwable $e) {
            Log::warning('Complaint status change notification failed', [
                'complaint_id' => $complaint->id,
                'complainant_id' => $complainant->id,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
