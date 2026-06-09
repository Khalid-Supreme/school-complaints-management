<?php

namespace App\Services;

use App\Models\Complaint;
use InvalidArgumentException;

class ComplaintWorkflowService
{
    protected const VALID_STATUSES = [
        'draft',
        'submitted',
        'under_review',
        'assigned',
        'in_progress',
        'resolved',
        'closed',
        'rejected',
        'reopened',
    ];

    /**
     * Transition a complaint to a new status.
     *
     * @param Complaint $complaint
     * @param string $newStatus
     * @return bool
     * @throws InvalidArgumentException
     */
    public function transitionStatus(Complaint $complaint, string $newStatus): bool
    {
        if (!in_array($newStatus, self::VALID_STATUSES)) {
            throw new InvalidArgumentException("Invalid status: {$newStatus}");
        }

        $complaint->status = $newStatus;

        if ($newStatus === 'closed') {
            $complaint->closed_at = now();
        } elseif ($newStatus === 'resolved' && !$complaint->resolved_at) {
            $complaint->resolved_at = now();
        } elseif ($newStatus === 'assigned' && !$complaint->assigned_at) {
            $complaint->assigned_at = now();
        }

        return $complaint->save();
    }
}
