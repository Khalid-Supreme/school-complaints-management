<?php

namespace App\Repositories;

use App\Models\Complaint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ComplaintRepository
{
    /**
     * Store a new complaint.
     *
     * @param array $data
     * @return Complaint
     */
    public function create(array $data): Complaint
    {
        return Complaint::create($data);
    }

    /**
     * Get complaints for a specific complainant.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByComplainant(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Complaint::with('category')
            ->where('complainant_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find a complaint by ID.
     *
     * @param int $id
     * @return Complaint|null
     */
    public function findById(int $id): ?Complaint
    {
        return Complaint::with(['category', 'complainant'])->find($id);
    }

    /**
     * Update a complaint.
     *
     * @param Complaint $complaint
     * @param array $data
     * @return bool
     */
    public function update(Complaint $complaint, array $data): bool
    {
        return $complaint->update($data);
    }
}
