<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $staffRole = Role::where('slug', 'staff')->first();
        $studentRole = Role::where('slug', 'student')->first();

        $dept = fn(string $slug) => Department::where('slug', $slug)->value('id');

        // Upsert a user by email, restoring soft-deleted rows so the unique
        // email constraint never collides with a previously-deleted seed user.
        $upsert = function (string $email, array $attributes): User {
            $user = User::withTrashed()->firstOrNew(['email' => $email]);
            $user->fill($attributes);
            $user->save();
            if ($user->trashed()) {
                $user->restore();
            }

            return $user;
        };

        // 1. Admin - keyed by institution_id so the existing admin is
        // updated (email, name) rather than inserting a duplicate.
        if ($adminRole) {
            $admin = User::withTrashed()
                ->where('institution_id', 'STF-2026-001')
                ->firstOrNew();

            $admin->fill([
                'email' => 'richunclekhalid@gmail.com',
                'role_id' => $adminRole->id,
                'first_name' => 'Khalid',
                'last_name' => 'Supreme',
                'institution_id' => 'STF-2026-001',
                'password' => Hash::make(hash('sha256', '123Asd?!;')),
                'department_id' => $dept('vc-office'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $admin->save();
            if ($admin->trashed()) {
                $admin->restore();
            }
        }

        // 2. Staff - uses staff role
        if ($staffRole) {
            $upsert('staff@example.com', [
                'role_id' => $staffRole->id,
                'first_name' => 'Staff',
                'last_name' => 'Sarah',
                'institution_id' => 'STF-2026-002',
                'password' => Hash::make(hash('sha256', '123Asd?!;')),
                'department_id' => $dept('sciences'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        // 3. Student
        if ($studentRole) {
            $upsert('student@example.com', [
                'role_id' => $studentRole->id,
                'first_name' => 'Student',
                'last_name' => 'Jane',
                'institution_id' => 'STD-2026-001',
                'password' => Hash::make(hash('sha256', '123Asd?!;')),
                'department_id' => $dept('computing-it'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }
    }
}
