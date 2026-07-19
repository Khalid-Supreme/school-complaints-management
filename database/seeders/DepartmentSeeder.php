<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            // Academic — available to both students and staff
            ['name' => 'College of Engineering', 'slug' => 'engineering', 'type' => 'academic'],
            ['name' => 'College of Sciences', 'slug' => 'sciences', 'type' => 'academic'],
            ['name' => 'College of Social Sciences', 'slug' => 'social-sciences', 'type' => 'academic'],
            ['name' => 'College of Arts & Humanities', 'slug' => 'arts-humanities', 'type' => 'academic'],
            ['name' => 'College of Education', 'slug' => 'education', 'type' => 'academic'],
            ['name' => 'College of Law', 'slug' => 'law', 'type' => 'academic'],
            ['name' => 'College of Management Sciences', 'slug' => 'management-sciences', 'type' => 'academic'],
            ['name' => 'College of Health Sciences', 'slug' => 'health-sciences', 'type' => 'academic'],
            ['name' => 'College of Agriculture', 'slug' => 'agriculture', 'type' => 'academic'],
            ['name' => 'College of Computing & IT', 'slug' => 'computing-it', 'type' => 'academic'],

            // Administrative — available to staff only
            ['name' => 'Vice Chancellor\'s Office', 'slug' => 'vc-office', 'type' => 'administrative'],
            ['name' => 'Academic Registry', 'slug' => 'academic-registry', 'type' => 'administrative'],
            ['name' => 'Student Affairs Division', 'slug' => 'student-affairs', 'type' => 'administrative'],
            ['name' => 'IT & E-Learning Directorate', 'slug' => 'it-directorate', 'type' => 'administrative'],
            ['name' => 'Bursary & Finance', 'slug' => 'bursary-finance', 'type' => 'administrative'],
            ['name' => 'University Library', 'slug' => 'library', 'type' => 'administrative'],
            ['name' => 'Works & Physical Planning', 'slug' => 'works-planning', 'type' => 'administrative'],
            ['name' => 'Health Services', 'slug' => 'health-services', 'type' => 'administrative'],
            ['name' => 'Security Unit', 'slug' => 'security-unit', 'type' => 'administrative'],
            ['name' => 'Corporate Affairs & PR', 'slug' => 'corporate-affairs', 'type' => 'administrative'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['slug' => $dept['slug']],
                $dept
            );
        }
    }
}
