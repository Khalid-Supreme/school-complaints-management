<?php

namespace App\Events;

use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class ChatMessageCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Complaint $complaint,
        public readonly ComplaintMessage $message,
        public readonly User $sender
    ) {}
}
