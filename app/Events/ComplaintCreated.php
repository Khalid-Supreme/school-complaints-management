<?php

namespace App\Events;

use App\Models\Complaint;
use Illuminate\Foundation\Events\Dispatchable;

class ComplaintCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Complaint $complaint
    ) {}
}
