<?php

namespace App\Events;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class ComplaintStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Complaint $complaint,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly ?User $actor = null
    ) {}
}
