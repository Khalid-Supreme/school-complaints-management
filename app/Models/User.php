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
}
