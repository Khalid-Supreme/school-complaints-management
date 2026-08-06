<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Notifications\ResetPasswordNotification;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'role_id',
    'name',
    'title',
    'gender',
    'email',
    'institution_id',
    'password',
    'phone',
    'department_id',
    'is_active',
    'last_login_at',
    'email_verified_at'
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the role associated with the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the login attempts for the user.
     */
    public function loginAttempts(): HasMany
    {
        return $this->hasMany(LoginAttempt::class);
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Check if the user is an Administrator (Admin or Sub-Admin).
     */
    public function isAdministrator(): bool
    {
        return app(\App\Services\PermissionService::class)->isAdministrator($this);
    }

    /**
     * Check if the user is a Super Admin (Admin only).
     */
    public function isSuperAdmin(): bool
    {
        return app(\App\Services\PermissionService::class)->isSuperAdmin($this);
    }

    /**
     * Check if the user is a Sub-Admin.
     */
    public function isSubAdmin(): bool
    {
        return app(\App\Services\PermissionService::class)->isSubAdmin($this);
    }

    /**
     * Check if the user can manage users.
     */
    public function canManageUsers(): bool
    {
        return app(\App\Services\PermissionService::class)->canManageUsers($this);
    }

    /**
     * Check if the user can manage complaints.
     */
    public function canManageComplaints(): bool
    {
        return app(\App\Services\PermissionService::class)->canManageComplaints($this);
    }

    /**
     * Check if the user can manage staff.
     */
    public function canManageStaff(): bool
    {
        return app(\App\Services\PermissionService::class)->canManageStaff($this);
    }

    /**
     * Check if the user can assign roles.
     */
    public function canAssignRoles(): bool
    {
        return app(\App\Services\PermissionService::class)->canAssignRoles($this);
    }

    /**
     * Check if the user can manage settings.
     */
    public function canManageSettings(): bool
    {
        return app(\App\Services\PermissionService::class)->canManageSettings($this);
    }

    /**
     * Check if the user can access admin dashboard.
     */
    public function canAccessAdminDashboard(): bool
    {
        return app(\App\Services\PermissionService::class)->canAccessAdminDashboard($this);
    }

    /**
     * Check if the user can access security dashboard.
     */
    public function canAccessSecurityDashboard(): bool
    {
        return app(\App\Services\PermissionService::class)->canAccessSecurityDashboard($this);
    }

    /**
     * Check if the user can view all users.
     */
    public function canViewAllUsers(): bool
    {
        return app(\App\Services\PermissionService::class)->canViewAllUsers($this);
    }

    /**
     * Check if the user can create staff.
     */
    public function canCreateStaff(): bool
    {
        return app(\App\Services\PermissionService::class)->canCreateStaff($this);
    }

    /**
     * Check if the user can promote to Sub-Admin.
     */
    public function canPromoteToSubAdmin(): bool
    {
        return app(\App\Services\PermissionService::class)->canPromoteToSubAdmin($this);
    }

    /**
     * Check if the user can demote Sub-Admin.
     */
    public function canDemoteSubAdmin(): bool
    {
        return app(\App\Services\PermissionService::class)->canDemoteSubAdmin($this);
    }

    /**
     * Get roles that the user can assign to others.
     */
    public function getAssignableRoles(): array
    {
        return app(\App\Services\PermissionService::class)->getAssignableRoles($this);
    }

    /**
     * Get roles that the user can manage.
     */
    public function getManageableRoles(): array
    {
        return app(\App\Services\PermissionService::class)->getManageableRoles($this);
    }

    /**
     * Check if the user can assign a specific role.
     */
    public function canAssignRole(string $roleSlug): bool
    {
        return app(\App\Services\PermissionService::class)->canAssignRole($this, $roleSlug);
    }

    /**
     * Check if the user can manage a specific role.
     */
    public function canManageRole(string $roleSlug): bool
    {
        return app(\App\Services\PermissionService::class)->canManageRole($this, $roleSlug);
    }
}
