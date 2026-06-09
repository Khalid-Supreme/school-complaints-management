<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
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

        if ($adminRole) {
            User::firstOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'role_id' => $adminRole->id,
                    'name' => 'System Admin',
                    'password' => Hash::make('password'),
                    'department' => 'IT',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
