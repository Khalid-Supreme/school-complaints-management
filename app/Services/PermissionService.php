<?php

namespace App\Services;

use App\Models\User;

class PermissionService
{
    /**
     * Determine if the user is an Administrator (Admin or Sub-Admin).
     */
    public function isAdministrator(User $user): bool
    {
        return in_array($user->role?->slug, ['admin', 'sub_admin'], true);
    }

    /**
     * Determine if the user is a Super Admin (Admin only, not Sub-Admin).
     */
    public function isSuperAdmin(User $user): bool
    {
        return $user->role?->slug === 'admin';
    }

    /**
     * Determine if the user is a Sub-Admin.
     */
    public function isSubAdmin(User $user): bool
    {
        return $user->role?->slug === 'sub_admin';
    }

    /**
     * Determine if the user can manage users (create, edit, delete, change roles).
     * Currently: Admin only. Sub-Admins cannot manage users.
     */
    public function canManageUsers(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Determine if the user can manage complaints (view all, assign, update status).
     * Currently: Admin and Sub-Admin.
     */
    public function canManageComplaints(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    /**
     * Determine if the user can manage staff (create, edit, delete staff accounts).
     * Currently: Admin only. Sub-Admins cannot create staff.
     */
    public function canManageStaff(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Determine if the user can assign roles to other users.
     * Currently: Admin only.
     */
    public function canAssignRoles(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Determine if the user can manage system settings.
     * Currently: Admin and Sub-Admin.
     */
    public function canManageSettings(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    /**
     * Determine if the user can access the admin dashboard.
     * Currently: Admin and Sub-Admin.
     */
    public function canAccessAdminDashboard(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    /**
     * Determine if the user can access security dashboard.
     * Currently: Admin, Sub-Admin, and Security.
     */
    public function canAccessSecurityDashboard(User $user): bool
    {
        return in_array($user->role?->slug, ['admin', 'sub_admin', 'security'], true);
    }

    /**
     * Determine if the user can view all users (students and staff).
     * Currently: Admin and Sub-Admin.
     */
    public function canViewAllUsers(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    /**
     * Determine if the user can create new staff accounts.
     * Currently: Admin only.
     */
    public function canCreateStaff(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Determine if the user can promote a staff member to Sub-Admin.
     * Currently: Admin only.
     */
    public function canPromoteToSubAdmin(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Determine if the user can demote a Sub-Admin back to Staff.
     * Currently: Admin only.
     */
    public function canDemoteSubAdmin(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Get all roles that the current user can assign to others.
     * Admin: staff, complaint_officer, sub_admin, security
     * Sub-Admin: (none - cannot assign roles)
     */
    public function getAssignableRoles(User $user): array
    {
        if ($this->isSuperAdmin($user)) {
            return [
                ['label' => 'Staff', 'value' => 'staff'],
                ['label' => 'Complaint Officer', 'value' => 'complaint_officer'],
                ['label' => 'Sub-Administrator', 'value' => 'sub_admin'],
                ['label' => 'Security', 'value' => 'security'],
            ];
        }

        if ($this->isSubAdmin($user)) {
            return [];
        }

        return [];
    }

    /**
     * Get all roles that the current user can view/manage in user management.
     */
    public function getManageableRoles(User $user): array
    {
        if ($this->isSuperAdmin($user)) {
            return ['student', 'staff', 'complaint_officer', 'sub_admin', 'security'];
        }

        if ($this->isSubAdmin($user)) {
            return ['student', 'staff', 'complaint_officer'];
        }

        return [];
    }

    /**
     * Check if a target role can be assigned by the current user.
     */
    public function canAssignRole(User $user, string $targetRoleSlug): bool
    {
        $assignableRoles = $this->getAssignableRoles($user);
        return collect($assignableRoles)->pluck('value')->contains($targetRoleSlug);
    }

    /**
     * Check if the user can view/manage users of a specific role.
     */
    public function canManageRole(User $user, string $targetRoleSlug): bool
    {
        $manageableRoles = $this->getManageableRoles($user);
        return in_array($targetRoleSlug, $manageableRoles, true);
    }
}