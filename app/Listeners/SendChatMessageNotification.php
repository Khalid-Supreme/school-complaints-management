<?php

namespace App\Listeners;

use App\Events\ChatMessageCreated;
use App\Models\User;
use App\Services\AesEncryptionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendChatMessageNotification
{
    public function __construct(
        protected AesEncryptionService $encryption
    ) {}

    public function handle(ChatMessageCreated $event): void
    {
        $complaint = $event->complaint->fresh(['category', 'complainant.role', 'currentAssignment.assignedTo.role']);
        $sender = $event->sender->loadMissing('role');

        if (! $complaint || ! $sender) {
            return;
        }

        $recipients = $this->resolveRecipients($complaint, $sender);

        if (empty($recipients)) {
            return;
        }

        // Deduplicate by user id in case logic ever overlaps
        $unique = [];
        foreach ($recipients as $user) {
            $unique[$user->id] = $user;
        }
        $recipients = array_values($unique);

        $title = $this->encryption->decrypt($complaint->title_encrypted) ?? 'Complaint';
        $preview = $this->buildPreview($event->message->message_encrypted);
        $senderName = $sender->full_name ?? trim(($sender->first_name ?? '').' '.($sender->last_name ?? '')) ?: $sender->email;
        $sentAt = $event->message->created_at?->toDateTimeString() ?? now()->toDateTimeString();
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        foreach ($recipients as $index => $recipient) {
            if ($index > 0) {
                usleep(3000000);
            }

            if (! $recipient->is_active || ! $recipient->email_verified_at) {
                continue;
            }

            $email = $recipient->email;

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            // Re-verify recipient is authorized to view this complaint
            if (! $this->isAuthorizedToView($complaint, $recipient)) {
                continue;
            }

            $viewUrl = $frontendUrl ? $this->buildViewUrl($frontendUrl, $complaint, $recipient) : null;

            try {
                Mail::send('emails.chat-new-message', [
                    'complaint' => $complaint,
                    'title' => $title,
                    'senderName' => $senderName,
                    'sentAt' => $sentAt,
                    'preview' => $preview,
                    'viewUrl' => $viewUrl,
                ], function ($message) use ($recipient, $complaint) {
                    $message->to($recipient->email, $recipient->full_name)
                        ->subject("New Message on Complaint — {$complaint->reference_no}");
                });
            } catch (\Throwable $e) {
                Log::warning('Chat message notification failed', [
                    'complaint_id' => $complaint->id,
                    'message_id' => $event->message->id,
                    'sender_id' => $sender->id,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Centralized recipient rules — never trust frontend input.
     *
     * @return User[]
     */
    protected function resolveRecipients($complaint, $sender): array
    {
        $senderId = (int) $sender->id;
        $senderSlug = $sender->role?->slug;
        $currentOfficer = $complaint->currentAssignment?->assignedTo;

        // Rule 1: complainant sends -> currently assigned officer
        if ((int) $complaint->complainant_id === $senderId) {
            if ($currentOfficer && $currentOfficer->role?->slug === 'complaint_officer') {
                return [$currentOfficer];
            }

            return [];
        }

        // Rule 2: Admin (admin or sub_admin) sends -> complainant + officer
        if (in_array($senderSlug, ['admin', 'sub_admin'], true)) {
            $recipients = [];

            if ($complaint->complainant) {
                $recipients[] = $complaint->complainant;
            }

            if ($currentOfficer && $currentOfficer->role?->slug === 'complaint_officer') {
                $recipients[] = $currentOfficer;
            }

            return array_filter($recipients, fn ($u) => (int) $u->id !== $senderId);
        }

        // Rule 3: complaint officer sends -> complainant
        if ($senderSlug === 'complaint_officer' && $currentOfficer && (int) $currentOfficer->id === $senderId) {
            return $complaint->complainant ? [$complaint->complainant] : [];
        }

        return [];
    }

    protected function isAuthorizedToView($complaint, $user): bool
    {
        $slug = $user->role?->slug;

        if (in_array($slug, ['admin', 'sub_admin'], true)) {
            return true;
        }

        if ($slug === 'complaint_officer') {
            return (int) ($complaint->currentAssignment?->assigned_to) === (int) $user->id;
        }

        return (int) $complaint->complainant_id === (int) $user->id;
    }

    protected function buildPreview(?string $encrypted): string
    {
        $decrypted = $this->encryption->decrypt((string) $encrypted) ?? '';

        // Strip HTML, collapse whitespace/newlines, limit length
        $plain = trim(strip_tags($decrypted));
        $plain = preg_replace('/\s+/', ' ', $plain) ?? $plain;

        return Str::limit($plain, 200);
    }

    protected function buildViewUrl(string $frontendUrl, $complaint, $recipient): string
    {
        $slug = $recipient->role?->slug;
        $id = $complaint->id;

        $prefix = match ($slug) {
            'admin', 'sub_admin' => '/admin/complaints',
            'complaint_officer' => '/officer/complaints',
            'staff' => '/staff/complaints',
            default => '/student/complaints',
        };

        return $frontendUrl.$prefix.'/'.$id;
    }
}
