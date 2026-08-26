<?php

namespace App\Listeners;

use App\Events\ComplaintCreated;
use App\Models\User;
use App\Services\AesEncryptionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewComplaintAdminNotification
{
    public function __construct(
        protected AesEncryptionService $encryption
    ) {}

    public function handle(ComplaintCreated $event): void
    {
        $complaint = $event->complaint->fresh(['category', 'complainant.role', 'complainant.department']);

        if (! $complaint) {
            return;
        }

        $admins = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->get(['id', 'email', 'first_name', 'last_name', 'title']);

        if ($admins->isEmpty()) {
            return;
        }

        $title = $this->encryption->decrypt($complaint->title_encrypted) ?? 'Complaint';
        $category = $complaint->category?->name ?? 'Uncategorized';
        $complainantName = $complaint->complainant?->full_name ?? 'Unknown';
        $submittedAt = $complaint->submitted_at?->toDateTimeString() ?? $complaint->created_at?->toDateTimeString() ?? now()->toDateTimeString();
        $status = $complaint->status;
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        foreach ($admins as $index => $admin) {
            if ($index > 0) {
                usleep(3000000);
            }

            $recipientEmail = $admin->email;

            if (! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            try {
                Mail::send('emails.complaint-created', [
                    'complaint' => $complaint,
                    'title' => $title,
                    'category' => $category,
                    'complainantName' => $complainantName,
                    'submittedAt' => $submittedAt,
                    'status' => $status,
                    'frontendUrl' => $frontendUrl,
                ], function ($message) use ($admin, $complaint) {
                    $message->to($admin->email, $admin->full_name)
                        ->subject("New Complaint Submitted — {$complaint->reference_no}");
                });
            } catch (\Throwable $e) {
                Log::warning('New complaint admin notification failed', [
                    'complaint_id' => $complaint->id,
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
