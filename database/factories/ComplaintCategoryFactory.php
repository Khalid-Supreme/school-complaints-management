<?php

namespace Database\Factories;

use App\Models\ComplaintCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ComplaintCategory>
 */
class ComplaintCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'default_priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'is_active' => true,
        ];
    }
}
