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
     * Visible to: admin, complaint_officer, or the complainant themselves.
     */
    public function view(User $user, Complaint $complaint): bool
    {
        if (in_array($user->role->slug, ['admin', 'complaint_officer'], true)) {
            return true;
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
}
