<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Support\InputSanitizer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ChatService
{
    /**
     * Send a message within a complaint conversation.
     */
    public function sendMessage(int $complaintId, string $message): ComplaintMessage
    {
        return ComplaintMessage::create([
            'complaint_id' => $complaintId,
            'user_id' => Auth::id(),
            'message' => InputSanitizer::clean($message),
        ]);
    }

    /**
     * Retrieve messages for a specific complaint.
     */
    public function getMessages(int $complaintId, int $perPage = 20): LengthAwarePaginator
    {
        return ComplaintMessage::where('complaint_id', $complaintId)
            ->with(['user:id,name,first_name,last_name,title,email,role_id', 'user.role:id,slug'])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
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
