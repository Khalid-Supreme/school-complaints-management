<?php

namespace App\Repositories;

use App\Models\Complaint;
use App\Models\ComplaintAssignment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ComplaintAssignmentRepository
{
    /**
     * Get all current assignments for a staff member.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getStaffAssignments(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return ComplaintAssignment::with(['complaint.category', 'assignedBy'])
            ->where('assigned_to', $userId)
            ->where('is_current', true)
            ->orderBy('assigned_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get the current assignment for a complaint.
     *
     * @param int $complaintId
     * @return ComplaintAssignment|null
     */
    public function getCurrentAssignment(int $complaintId): ?ComplaintAssignment
    {
        return ComplaintAssignment::with(['assignedTo', 'assignedBy'])
            ->where('complaint_id', $complaintId)
            ->where('is_current', true)
            ->first();
    }

    /**
     * Release the current assignment for a complaint (mark as not current).
     *
     * @param int $complaintId
     * @return void
     */
    public function releaseCurrentAssignment(int $complaintId): void
    {
        ComplaintAssignment::where('complaint_id', $complaintId)
            ->where('is_current', true)
            ->update([
                'is_current' => false,
                'released_at' => now(),
            ]);
    }

    /**
     * Create a new assignment record.
     *
     * @param array $data
     * @return ComplaintAssignment
     */
    public function create(array $data): ComplaintAssignment
    {
        return ComplaintAssignment::create($data);
    }

    /**
     * Get assignment history for a complaint.
     *
     * @param int $complaintId
     * @return Collection
     */
    public function getHistory(int $complaintId): Collection
    {
        return ComplaintAssignment::with(['assignedTo', 'assignedBy'])
            ->where('complaint_id', $complaintId)
            ->orderBy('assigned_at', 'desc')
            ->get();
    }
}
