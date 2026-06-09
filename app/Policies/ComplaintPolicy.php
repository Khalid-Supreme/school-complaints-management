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
     */
    public function view(User $user, Complaint $complaint): bool
    {
        if ($user->role->slug === 'admin' || $user->role->slug === 'security') {
            return true;
        }

        if ($user->role->slug === 'staff') {
            // Check if assigned (simplified for now, full assignment logic later)
            return true;
        }

        return $user->id === $complaint->complainant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role->slug === 'complainant';
    }
}
