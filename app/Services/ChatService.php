<?php

namespace App\Services;

use App\Events\ChatMessageCreated;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Support\InputSanitizer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatService
{
    protected AesEncryptionService $encryption;

    public function __construct(AesEncryptionService $encryption)
    {
        $this->encryption = $encryption;
    }

    /**
     * Send a message within a complaint conversation.
     */
    public function sendMessage(int $complaintId, string $message): ComplaintMessage
    {
        $record = DB::transaction(function () use ($complaintId, $message) {
            return ComplaintMessage::create([
                'complaint_id' => $complaintId,
                'user_id' => Auth::id(),
                'message_encrypted' => $this->encryption->encrypt(InputSanitizer::clean($message)),
            ]);
        });

        // Dispatch only after the surrounding transaction has committed.
        // If the insert is rolled back, no notification is sent.
        $freshMessage = $record->fresh(['user.role']);
        $complaint = Complaint::with(['category', 'complainant.role', 'currentAssignment.assignedTo.role'])->find($complaintId);
        $sender = $freshMessage->user;

        if ($complaint && $sender) {
            ChatMessageCreated::dispatch($complaint, $freshMessage, $sender);
        }

        return $record;
    }

    /**
     * Retrieve messages for a specific complaint.
     */
    public function getMessages(int $complaintId, int $perPage = 20): LengthAwarePaginator
    {
        $paginator = ComplaintMessage::where('complaint_id', $complaintId)
            ->with(['user:id,first_name,last_name,title,email,role_id', 'user.role:id,slug'])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function (ComplaintMessage $message) {
            $message->message = $this->encryption->decrypt((string) $message->message_encrypted) ?? '';
            unset($message->message_encrypted);

            return $message;
        });

        return $paginator;
    }

    /**
     * Mark messages as read for a specific user.
     */
    public function markAsRead(int $complaintId, int $userId): void
    {
        ComplaintMessage::where('complaint_id', $complaintId)
            ->where('user_id', '!=', $userId)
            ->update(['is_read' => true]);
    }
}
