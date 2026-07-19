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
        $complaintOfficerRole = Role::where('slug', 'complaint_officer')->first();
        $securityRole = Role::where('slug', 'security')->first();

        $dept = fn(string $slug) => Department::where('slug', $slug)->value('id');

        // 1. Admin
        if ($adminRole) {
            User::updateOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'role_id' => $adminRole->id,
                    'name' => 'Admin Supreme',
                    'institution_id' => 'STF-2026-001',
                    'password' => Hash::make(hash('sha256', 'password')),
                    'department_id' => $dept('vc-office'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        // 2. Staff (complaint handlers) - uses staff role
        if ($staffRole) {
            User::updateOrCreate(
                ['email' => 'staff@example.com'],
                [
                    'role_id' => $staffRole->id,
                    'name' => 'Staff Sarah',
                    'institution_id' => 'STF-2026-099',
                    'password' => Hash::make(hash('sha256', 'password')),
                    'department_id' => $dept('sciences'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        // 3. Student
        if ($studentRole) {
            // Fix existing bad rows where a student accidentally has the same institution_id as admin.
            // users.institution_id is UNIQUE(), so we must ensure we generate non-colliding IDs.
            $adminInstitutionId = 'STF-2026-001';

            $badStudents = User::where('role_id', $studentRole->id)
                ->where('institution_id', $adminInstitutionId)
                ->get();

            if ($badStudents->isNotEmpty()) {
                $candidate = 901; // start after existing seed ranges
                foreach ($badStudents as $badStudent) {
                    do {
                        $newInstitutionId = 'STD-2026-' . str_pad((string)$candidate, 3, '0', STR_PAD_LEFT);
                        $candidate++;
                    } while (User::where('institution_id', $newInstitutionId)->exists());

                    $badStudent->update([
                        'institution_id' => $newInstitutionId,
                    ]);
                }
            }

            // Seed additional students with distinct institution_id values.
            // Cybersecurity
            User::updateOrCreate(
                ['email' => 'cybersecurity-student-1@example.com'],
                [
                    'role_id' => $studentRole->id,
                    'name' => 'Student Aisha',
                    'institution_id' => 'STD-2026-201',
                    'password' => Hash::make(hash('sha256', 'password')),
                    'department_id' => $dept('computing-it'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            User::updateOrCreate(
                ['email' => 'cybersecurity-student-2@example.com'],
                [
                    'role_id' => $studentRole->id,
                    'name' => 'Student Bashir',
                    'institution_id' => 'STD-2026-202',
                    'password' => Hash::make(hash('sha256', 'password')),
                    'department_id' => $dept('computing-it'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Arabic
            User::updateOrCreate(
                ['email' => 'arabic-student-1@example.com'],
                [
                    'role_id' => $studentRole->id,
                    'name' => 'Student Abiodun',
                    'institution_id' => 'STD-2026-301',
                    'password' => Hash::make(hash('sha256', 'password')),
                    'department_id' => $dept('arts-humanities'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            User::updateOrCreate(
                ['email' => 'arabic-student-2@example.com'],
                [
                    'role_id' => $studentRole->id,
                    'name' => 'Student Naimah',
                    'institution_id' => 'STD-2026-302',
                    'password' => Hash::make(hash('sha256', 'password')),
                    'department_id' => $dept('arts-humanities'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // (Optional) keep the original student@example.com, but avoid reusing STD-2026-001
            // to prevent any future collisions; choose a new unique institution_id.
            User::updateOrCreate(
                ['email' => 'student@example.com'],
                [
                    'role_id' => $studentRole->id,
                    'name' => 'Student Jane',
                    'institution_id' => 'STD-2026-101',
                    'password' => Hash::make(hash('sha256', 'password')),
                    'department_id' => $dept('computing-it'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        // 4. Complaint Officer
        if ($complaintOfficerRole) {
            User::updateOrCreate(
                ['email' => 'officer@example.com'],
                [
                    'role_id' => $complaintOfficerRole->id,
                    'name' => 'Officer Aliyah',
                    'institution_id' => 'STF-2026-002',
                    'password' => Hash::make(hash('sha256', 'password')),
                    'department_id' => $dept('student-affairs'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            User::updateOrCreate(
                ['email' => 'officer2@example.com'],
                [
                    'role_id' => $complaintOfficerRole->id,
                    'name' => 'Officer Yunus',
                    'institution_id' => 'STF-2026-004',
                    'password' => Hash::make(hash('sha256', 'password')),
                    'department_id' => $dept('academic-registry'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        // 5. Security Analyst
        if ($securityRole) {
            User::updateOrCreate(
                ['email' => 'security@example.com'],
                [
                    'role_id' => $securityRole->id,
                    'name' => 'Security Danjuma',
                    'institution_id' => 'STF-2026-003',
                    'password' => Hash::make(hash('sha256', 'password')),
                    'department_id' => $dept('security-unit'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
