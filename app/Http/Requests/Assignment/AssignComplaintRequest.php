<?php

namespace App\Http\Requests\Assignment;

use Illuminate\Foundation\Http\FormRequest;

class AssignComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Gate in controller
    }

    public function rules(): array
    {
        return [
            'assigned_to' => ['required', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.required' => 'Please select a staff member to assign this complaint to.',
            'assigned_to.exists' => 'The selected staff member does not exist.',
        ];
    }
}
