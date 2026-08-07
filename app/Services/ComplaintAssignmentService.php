<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\ComplaintAssignment;
use App\Models\User;
use App\Repositories\ComplaintAssignmentRepository;
use Illuminate\Validation\ValidationException;

class ComplaintAssignmentService
{
    protected ComplaintAssignmentRepository $repository;
    protected ComplaintWorkflowService $workflow;
    protected AesEncryptionService $encryption;

    public function __construct(
        ComplaintAssignmentRepository $repository,
        ComplaintWorkflowService $workflow,
        AesEncryptionService $encryption
    ) {
        $this->repository = $repository;
        $this->workflow = $workflow;
        $this->encryption = $encryption;
    }

    /**
     * Assign a complaint to a staff member.
     *
     * @param Complaint $complaint
     * @param int $assignedToId  The staff user ID to assign to
     * @param int $assignedById  The admin user ID making the assignment
     * @param string|null $note  Optional assignment note
     * @return ComplaintAssignment
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

        if (!$staffUser) {
            throw ValidationException::withMessages([
                'assigned_to' => 'The selected staff member does not exist.',
            ]);
        }

        if (!in_array($staffUser->role->slug, ['complaint_officer'], true)) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Complaints can only be assigned to complaint officers.',
            ]);
        }

        // Release the existing current assignment if any
        $this->repository->releaseCurrentAssignment($complaint->id);

        // Create the new assignment
        $assignment = $this->repository->create([
            'complaint_id' => $complaint->id,
            'assigned_to' => $assignedToId,
            'assigned_by' => $assignedById,
            'assignment_note_encrypted' => $note ? $this->encryption->encrypt($note) : null,
            'is_current' => true,
            'assigned_at' => now(),
        ]);

        // Transition complaint status to 'assigned'
        $this->workflow->transitionStatus($complaint, 'assigned');

        return $assignment->load(['assignedTo', 'assignedBy']);
    }

    /**
     * Get all assigned complaints for a staff member (with decrypted complaint fields).
     *
     * @param int $staffUserId
     * @return array
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
     *
     * @param Complaint $complaint
     * @return array
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
