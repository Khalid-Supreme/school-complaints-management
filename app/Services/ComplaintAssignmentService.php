<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Events\ComplaintAssigned;
use App\Models\Complaint;
use App\Models\ComplaintAssignment;
use App\Models\User;
use App\Repositories\ComplaintAssignmentRepository;
use App\Support\InputSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComplaintAssignmentService
{
    protected ComplaintAssignmentRepository $repository;

    protected ComplaintWorkflowService $workflow;

    protected AesEncryptionService $encryption;

    protected AuditLogger $auditLogger;

    public function __construct(
        ComplaintAssignmentRepository $repository,
        ComplaintWorkflowService $workflow,
        AesEncryptionService $encryption,
        AuditLogger $auditLogger
    ) {
        $this->repository = $repository;
        $this->workflow = $workflow;
        $this->encryption = $encryption;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Assign a complaint to a staff member.
     *
     * @param  int  $assignedToId  The staff user ID to assign to
     * @param  int  $assignedById  The admin user ID making the assignment
     * @param  string|null  $note  Optional assignment note
     *
     * @throws ValidationException
     */
    public function assign(
        Complaint $complaint,
        int $assignedToId,
        int $assignedById,
        ?string $note = null
    ): ComplaintAssignment {
        // Validate staff user exists and has staff role
        $staffUser = User::with('role')->find($assignedToId);

        if (! $staffUser) {
            throw ValidationException::withMessages([
                'assigned_to' => 'The selected staff member does not exist.',
            ]);
        }

        if (! in_array($staffUser->role->slug, ['complaint_officer'], true)) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Complaints can only be assigned to complaint officers.',
            ]);
        }

        // Idempotency: if the complaint is already assigned to the same
        // officer and that assignment is current, return it without
        // creating a duplicate row or sending another notification.
        $current = $complaint->currentAssignment;
        if ($current && (int) $current->assigned_to === (int) $assignedToId && $current->is_current) {
            return $current->load(['assignedTo', 'assignedBy']);
        }

        $assignment = DB::transaction(function () use ($complaint, $assignedToId, $assignedById, $note) {
            // Release the existing current assignment if any
            $this->repository->releaseCurrentAssignment($complaint->id);

            // Create the new assignment
            $record = $this->repository->create([
                'complaint_id' => $complaint->id,
                'assigned_to' => $assignedToId,
                'assigned_by' => $assignedById,
                'assignment_note_encrypted' => $note ? $this->encryption->encrypt(InputSanitizer::clean($note)) : null,
                'is_current' => true,
                'assigned_at' => now(),
            ]);

            // Transition complaint status to 'assigned' (suppress the generic
            // status audit record: the dedicated 'complaint.assigned' event below
            // is the authoritative log entry for this action).
            $this->workflow->transitionStatus($complaint, 'assigned', audit: false);

            $this->auditLogger->log(
                AuditAction::ComplaintAssigned,
                User::find($assignedById),
                $complaint,
                null,
                [
                    'complaint_reference' => $complaint->reference_no,
                    'assigned_to_user_id' => $assignedToId,
                    'assigned_by' => $record->assignedBy?->full_name,
                ]
            );

            return $record;
        });

        $freshAssignment = $assignment->load(['assignedTo', 'assignedBy']);
        $freshComplaint = $complaint->fresh();

        // Dispatch only after commit — if the transaction rolls back, no email.
        ComplaintAssigned::dispatch($freshComplaint, $freshAssignment);

        return $freshAssignment;
    }

    /**
     * Get all assigned complaints for a staff member (with decrypted complaint fields).
     */
    public function getStaffAssignments(int $staffUserId): array
    {
        $paginator = $this->repository->getStaffAssignments($staffUserId);

        $items = collect($paginator->items())->map(function (ComplaintAssignment $assignment) {
            $complaintArray = $assignment->complaint->toArray();
            $complaintArray['title'] = $this->encryption->decrypt($assignment->complaint->title_encrypted);
            $complaintArray['description'] = $this->encryption->decrypt($assignment->complaint->description_encrypted);
            unset($complaintArray['title_encrypted'], $complaintArray['description_encrypted']);

            return [
                'id' => $assignment->id,
                'assigned_at' => $assignment->assigned_at,
                'assigned_by' => $assignment->assignedBy?->full_name,
                'complaint' => $complaintArray,
            ];
        });

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * Get assignment history for a complaint.
     */
    public function getComplaintAssignmentHistory(Complaint $complaint): array
    {
        $history = $this->repository->getHistory($complaint->id);

        return $history->map(function (ComplaintAssignment $a) {
            return [
                'id' => $a->id,
                'assigned_to' => $a->assignedTo?->full_name,
                'assigned_by' => $a->assignedBy?->full_name,
                'assigned_at' => $a->assigned_at,
                'released_at' => $a->released_at,
                'assignment_note' => $a->assignment_note_encrypted ? $this->encryption->decrypt($a->assignment_note_encrypted) : null,
                'is_current' => $a->is_current,
            ];
        })->toArray();
    }
}
