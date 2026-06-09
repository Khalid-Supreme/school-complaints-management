<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_no' => strtoupper(Str::random(10)),
            'complainant_id' => User::factory(),
            'category_id' => ComplaintCategory::factory(),
            'title_encrypted' => base64_encode(fake()->sentence()), // Dummy encryption for factory
            'description_encrypted' => base64_encode(fake()->paragraph()), // Dummy encryption for factory
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'status' => 'submitted',
            'source' => 'web',
            'submitted_at' => now(),
        ];
    }
}
