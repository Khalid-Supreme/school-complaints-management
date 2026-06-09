<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    /**
     * Find a user by email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Update user's last login time.
     *
     * @param User $user
     * @return bool
     */
    public function updateLastLogin(User $user): bool
    {
        $user->last_login_at = now();
        return $user->save();
    }
}
