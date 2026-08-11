<?php

namespace App\Http\Requests\Assignment;

use App\Support\ValidationRules;
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
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'note' => ValidationRules::assignmentNote(),
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.required' => 'Please select a staff member to assign this complaint to.',
            'assigned_to.integer' => 'The selected staff member is invalid.',
            'assigned_to.exists' => 'The selected staff member does not exist.',
            'note.max' => 'The note cannot be longer than 1000 characters.',
            'note.encoding' => 'The note contains characters that are not valid UTF-8.',
        ];
    }
}
