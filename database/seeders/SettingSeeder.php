<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::firstOrCreate([], [
            'app_name' => 'SchoolVoice',
            'contact_email' => 'youfoundkhalid@gmail.com',
            'contact_phone' => '+2348144964536',
            'address' => 'Abuja, Nigeria',
            'social_links' => [
                'twitter' => '',
                'facebook' => '',
                'linkedin' => '',
            ],
            'meta' => [
                'description' => 'Complaints Management System For Tertiary Institutions',
                'keywords' => 'complaints, school, management, student, staff',
            ],
        ]);
    }
}