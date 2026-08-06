<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'System Administrator with full access.',
                'is_system' => true,
            ],
            [
                'name' => 'Sub-Administrator',
                'slug' => 'sub_admin',
                'description' => 'Sub-Administrator with administrative access (restricted role management).',
                'is_system' => true,
            ],
            [
                'name' => 'Staff',
                'slug' => 'staff',
                'description' => 'Staff member to handle complaints.',
                'is_system' => true,
            ],
            [
                'name' => 'Student',
                'slug' => 'student',
                'description' => 'Student user who submits complaints.',
                'is_system' => true,
            ],
            [
                'name' => 'Complaint Officer',
                'slug' => 'complaint_officer',
                'description' => 'Complaint officer user who processes complaint resolutions.',
                'is_system' => true,
            ],
            [
                'name' => 'Security',
                'slug' => 'security',
                'description' => 'Security personnel to monitor IPS and logs.',
                'is_system' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
