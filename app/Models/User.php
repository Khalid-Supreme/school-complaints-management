<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'role_id',
    'first_name',
    'last_name',
    'name',
    'title',
    'gender',
    'email',
    'institution_id',
    'password',
    'phone',
    'department_id',
    'is_active',
    'email_verified_at',
])]
#[Hidden(['password', 'remember_token'])]
#[Appends(['full_name', 'full_name_with_title', 'initials', 'display_name'])]
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
        return ! is_null($this->email_verified_at);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Compose a display name from first and last names.
     * Single source of truth for name formatting so controllers never
     * duplicate this logic.
     */
    public static function composeName(?string $first, ?string $last): string
    {
        $name = trim(trim((string) $first).' '.trim((string) $last));

        // Guard against overflowing the users.name column (150 chars) when
        // combined first+last names exceed the column width. Only pathological
        // input is affected; regular names are returned unchanged.
        return mb_strlen($name) > 150 ? mb_substr($name, 0, 150) : $name;
    }

    /**
     * Get the user's full name.
     * Concatenates first_name and last_name, falls back to name column.
     */
    public function getFullNameAttribute(): string
    {
        $first = $this->first_name ?? '';
        $last = $this->last_name ?? '';
        $combined = trim("$first $last");

        return $combined !== '' ? $combined : $this->name ?? '';
    }

    /**
     * Get the user's full name including their title, e.g. "Dr. John Doe".
     * Students never have titles, so they always return the plain full name.
     */
    public function getFullNameWithTitleAttribute(): string
    {
        if ($this->isStudent() || blank($this->title)) {
            return $this->full_name;
        }

        $title = rtrim(trim((string) $this->title), '.').'.';

        return trim("$title {$this->full_name}");
    }

    /**
     * Get the user's initials, e.g. "JD" for "John Doe", "J" for "John".
     */
    public function getInitialsAttribute(): string
    {
        $first = $this->first_name ?? '';
        $last = $this->last_name ?? '';

        $initials = strtoupper(mb_substr($first, 0, 1).mb_substr($last, 0, 1));

        if ($initials !== '') {
            return $initials;
        }

        $words = preg_split('/\s+/', trim((string) $this->name)) ?: [];

        return strtoupper(mb_substr($words[0] ?? '', 0, 1));
    }

    /**
     * The name to display for the user. Exists so future formatting
     * changes happen in a single place.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Check if the user is a student.
     */
    public function isStudent(): bool
    {
        return $this->role?->slug === 'student';
    }

    /**
     * Check if the user is an Administrator (Admin or Sub-Admin).
     */
    public function isAdministrator(): bool
    {
        return app(PermissionService::class)->isAdministrator($this);
    }

    /**
     * Check if the user is a Super Admin (Admin only).
     */
    public function isSuperAdmin(): bool
    {
        return app(PermissionService::class)->isSuperAdmin($this);
    }

    /**
     * Check if the user is a Sub-Admin.
     */
    public function isSubAdmin(): bool
    {
        return app(PermissionService::class)->isSubAdmin($this);
    }

    /**
     * Check if the user can manage users.
     */
    public function canManageUsers(): bool
    {
        return app(PermissionService::class)->canManageUsers($this);
    }

    /**
     * Check if the user can manage complaints.
     */
    public function canManageComplaints(): bool
    {
        return app(PermissionService::class)->canManageComplaints($this);
    }

    /**
     * Check if the user can manage staff.
     */
    public function canManageStaff(): bool
    {
        return app(PermissionService::class)->canManageStaff($this);
    }

    /**
     * Check if the user can assign roles.
     */
    public function canAssignRoles(): bool
    {
        return app(PermissionService::class)->canAssignRoles($this);
    }

    /**
     * Check if the user can manage settings.
     */
    public function canManageSettings(): bool
    {
        return app(PermissionService::class)->canManageSettings($this);
    }

    /**
     * Check if the user can access admin dashboard.
     */
    public function canAccessAdminDashboard(): bool
    {
        return app(PermissionService::class)->canAccessAdminDashboard($this);
    }

    /**
     * Check if the user can access security dashboard.
     */
    public function canAccessSecurityDashboard(): bool
    {
        return app(PermissionService::class)->canAccessSecurityDashboard($this);
    }

    /**
     * Check if the user can view all users.
     */
    public function canViewAllUsers(): bool
    {
        return app(PermissionService::class)->canViewAllUsers($this);
    }

    /**
     * Check if the user can create staff.
     */
    public function canCreateStaff(): bool
    {
        return app(PermissionService::class)->canCreateStaff($this);
    }

    /**
     * Check if the user can promote to Sub-Admin.
     */
    public function canPromoteToSubAdmin(): bool
    {
        return app(PermissionService::class)->canPromoteToSubAdmin($this);
    }

    /**
     * Check if the user can demote Sub-Admin.
     */
    public function canDemoteSubAdmin(): bool
    {
        return app(PermissionService::class)->canDemoteSubAdmin($this);
    }

    /**
     * Get roles that the user can assign to others.
     */
    public function getAssignableRoles(): array
    {
        return app(PermissionService::class)->getAssignableRoles($this);
    }

    /**
     * Get roles that the user can manage.
     */
    public function getManageableRoles(): array
    {
        return app(PermissionService::class)->getManageableRoles($this);
    }

    /**
     * Check if the user can assign a specific role.
     */
    public function canAssignRole(string $roleSlug): bool
    {
        return app(PermissionService::class)->canAssignRole($this, $roleSlug);
    }

    /**
     * Check if the user can manage a specific role.
     */
    public function canManageRole(string $roleSlug): bool
    {
        return app(PermissionService::class)->canManageRole($this, $roleSlug);
    }
}
