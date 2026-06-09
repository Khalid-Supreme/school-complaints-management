<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\ComplaintAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplaintAssignment>
 */
class ComplaintAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'complaint_id' => Complaint::factory(),
            'assigned_to' => User::factory(),
            'assigned_by' => User::factory(),
            'assignment_note_encrypted' => null,
            'is_current' => true,
            'assigned_at' => now(),
            'released_at' => null,
        ];
    }
}
