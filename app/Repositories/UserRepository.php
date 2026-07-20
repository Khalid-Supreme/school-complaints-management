<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function updateLastLogin(User $user): bool
    {
        $user->last_login_at = now();
        return $user->save();
    }

    public function generateInstitutionId(string $prefix): string
    {
        $year = now()->year;
        $highest = User::where('institution_id', 'like', "{$prefix}-{$year}-%")
            ->pluck('institution_id')
            ->map(fn($value) => (int) preg_replace('/^.*-(\d+)$/', '$1', $value))
            ->max() ?? 0;

        return sprintf('%s-%s-%04d', $prefix, $year, $highest + 1);
    }
}
