<?php

namespace App\Events;

use App\Models\Complaint;
use App\Models\ComplaintAssignment;
use Illuminate\Foundation\Events\Dispatchable;

class ComplaintAssigned
{
    use Dispatchable;

    public function __construct(
        public readonly Complaint $complaint,
        public readonly ComplaintAssignment $assignment
    ) {}
}
