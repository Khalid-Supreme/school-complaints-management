<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\Complaint;
use InvalidArgumentException;

class ComplaintWorkflowService
{
    public const STATUSES = [
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

    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Transition a complaint to a new status.
     *
     * @throws InvalidArgumentException
     */
    public function transitionStatus(Complaint $complaint, string $newStatus, bool $audit = true): bool
    {
        if (! in_array($newStatus, self::STATUSES)) {
            throw new InvalidArgumentException("Invalid status: {$newStatus}");
        }

        $oldStatus = $complaint->status;

        if ($oldStatus === $newStatus) {
            return false;
        }

        $complaint->status = $newStatus;

        if ($newStatus === 'closed') {
            $complaint->closed_at = now();
        } elseif ($newStatus === 'resolved' && ! $complaint->resolved_at) {
            $complaint->resolved_at = now();
        } elseif ($newStatus === 'assigned' && ! $complaint->assigned_at) {
            $complaint->assigned_at = now();
        }

        $saved = $complaint->save();

        if ($audit && $saved) {
            $action = match ($newStatus) {
                'closed' => AuditAction::ComplaintClosed,
                'reopened' => AuditAction::ComplaintReopened,
                default => AuditAction::ComplaintStatusChanged,
            };

            $subject = $complaint->fresh();
            $this->auditLogger->log($action, auth()->user(), $subject, null, [
                'complaint_reference' => $subject->reference_no ?? $complaint->reference_no,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
        }

        return $saved;
    }
}
