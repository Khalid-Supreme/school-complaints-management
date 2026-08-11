<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Controller handles filtering by role
    }

    /**
     * Determine whether the user can view the model.
     * Visible to: admin, sub_admin, the complaint officer assigned to it,
     * or the complainant themselves.
     */
    public function view(User $user, Complaint $complaint): bool
    {
        if (in_array($user->role->slug, ['admin', 'sub_admin'], true)) {
            return true;
        }

        if ($user->role->slug === 'complaint_officer') {
            return $complaint->currentAssignment?->assigned_to === $user->id;
        }

        return $user->id === $complaint->complainant_id;
    }

    /**
     * Determine whether the user can create models.
     * Complainants are users with 'student' or 'staff' role.
     */
    public function create(User $user): bool
    {
        return in_array($user->role->slug, ['student', 'staff'], true);
    }

    /**
     * Determine whether the user can update a complaint's status.
     * Available to administrators and the complaint officer assigned to it.
     */
    public function update(User $user, Complaint $complaint): bool
    {
        if ($user->role->slug === 'admin') {
            return true;
        }

        if ($user->role->slug === 'complaint_officer') {
            return $complaint->currentAssignment?->assigned_to === $user->id;
        }

        return false;
    }
}
