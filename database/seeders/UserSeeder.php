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
                'password' => Hash::make(hash('sha256', 'Admin@321')),
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
                'password' => Hash::make(hash('sha256', 'Staff@321')),
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
                'password' => Hash::make(hash('sha256', 'Student@321')),
                'department_id' => $dept('computing-it'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        // 4. Additional demo students (9)
        if ($studentRole) {
            $students = [
                // [email, institution_id, first_name, last_name, gender, department_slug]
                ['amina.adebayo@gmail.com', 'STD-2026-0201', 'Amina', 'Adebayo', 'female', 'engineering'],
                ['chinedu.okeke@gmail.com', 'STD-2026-0202', 'Chinedu', 'Okeke', 'male', 'computing-it'],
                ['fatima.bello@gmail.com', 'STD-2026-0203', 'Fatima', 'Bello', 'female', 'health-sciences'],
                ['ibrahim.musa@gmail.com', 'STD-2026-0204', 'Ibrahim', 'Musa', 'male', 'sciences'],
                ['ngozi.eze@gmail.com', 'STD-2026-0205', 'Ngozi', 'Eze', 'female', 'management-sciences'],
                ['olawale.johnson@gmail.com', 'STD-2026-0206', 'Olawale', 'Johnson', 'male', 'education'],
                ['sarah.adewale@gmail.com', 'STD-2026-0207', 'Sarah', 'Adewale', 'female', 'social-sciences'],
                ['kwame.boateng@gmail.com', 'STD-2026-0208', 'Kwame', 'Boateng', 'male', 'law'],
                ['chidinma.okafor@gmail.com', 'STD-2026-0209', 'Chidinma', 'Okafor', 'female', 'agriculture'],
            ];

            foreach ($students as [$email, $institutionId, $firstName, $lastName, $gender, $deptSlug]) {
                $upsert($email, [
                    'role_id' => $studentRole->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'institution_id' => $institutionId,
                    'title' => null,
                    'gender' => $gender,
                    'password' => Hash::make(hash('sha256', 'Student@321')),
                    'department_id' => $dept($deptSlug),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            }
        }

        // 5. Additional staff (1 staff, 2 complaint officers, 1 sub admin)
        $complaintOfficerRole = Role::where('slug', 'complaint_officer')->first();
        $subAdminRole = Role::where('slug', 'sub_admin')->first();

        if ($staffRole && $complaintOfficerRole && $subAdminRole) {
            $staff = [
                // [email, institution_id, first_name, last_name, gender, title, role_id, department_slug]
                ['halima.bello@gmail.com', 'STF-2026-0201', 'Halima', 'Bello', 'female', 'Dr.', $staffRole->id, 'it-directorate'],
                ['musa.yakubu@gmail.com', 'STF-2026-0202', 'Musa', 'Yakubu', 'male', 'Mr.', $complaintOfficerRole->id, 'student-affairs'],
                ['chioma.nwosu@gmail.com', 'STF-2026-0203', 'Chioma', 'Nwosu', 'female', 'Mrs.', $complaintOfficerRole->id, 'academic-registry'],
                ['abdulrahman.kabir@gmail.com', 'STF-2026-0204', 'Abdulrahman', 'Kabir', 'male', 'Prof.', $subAdminRole->id, 'vc-office'],
            ];

            foreach ($staff as [$email, $institutionId, $firstName, $lastName, $gender, $title, $roleId, $deptSlug]) {
                $upsert($email, [
                    'role_id' => $roleId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'institution_id' => $institutionId,
                    'title' => $title,
                    'gender' => $gender,
                    'password' => Hash::make(hash('sha256', 'Staff@321')),
                    'department_id' => $dept($deptSlug),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            }
        }
    }
}
