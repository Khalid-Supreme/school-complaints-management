<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ComplaintCategory;

class ComplaintCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Academic Issues',
                'slug' => 'academic-issues',
                'description' => 'Issues related to teaching, grading, or courses.',
                'default_priority' => 'high',
                'is_active' => true,
            ],
            [
                'name' => 'Facilities & Infrastructure',
                'slug' => 'facilities-infrastructure',
                'description' => 'Issues related to buildings, equipment, or campus facilities.',
                'default_priority' => 'medium',
                'is_active' => true,
            ],
            [
                'name' => 'Administrative',
                'slug' => 'administrative',
                'description' => 'Issues with registration, fees, or administration.',
                'default_priority' => 'medium',
                'is_active' => true,
            ],
            [
                'name' => 'Harassment or Misconduct',
                'slug' => 'harassment-misconduct',
                'description' => 'Reports of inappropriate behavior or harassment.',
                'default_priority' => 'critical',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            ComplaintCategory::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
