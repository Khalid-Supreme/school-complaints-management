<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Support\InputSanitizer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

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
        return ComplaintMessage::create([
            'complaint_id' => $complaintId,
            'user_id' => Auth::id(),
            'message_encrypted' => $this->encryption->encrypt(InputSanitizer::clean($message)),
        ]);
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
