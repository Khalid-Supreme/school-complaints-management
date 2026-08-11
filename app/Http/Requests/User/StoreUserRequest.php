<?php

namespace App\Http\Requests\User;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization enforced in the controller (canManageUsers).
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'role' => ValidationRules::creatableRoleSlug(),
            'first_name' => ValidationRules::firstName(),
            'last_name' => ValidationRules::lastName(),
            'email' => ValidationRules::uniqueEmail(),
            'gender' => ValidationRules::gender(),
            'department' => ValidationRules::departmentId(),
            'password' => ValidationRules::optionalPassword(),
        ];

        if (in_array($this->input('role'), ['staff', 'complaint_officer'], true)) {
            $rules['title'] = ValidationRules::title();
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Please select a role.',
            'role.in' => 'The selected role is invalid.',
            'first_name.required' => 'Please enter the first name.',
            'first_name.regex' => 'The first name may only contain letters, spaces, hyphens and apostrophes.',
            'last_name.required' => 'Please enter the last name.',
            'last_name.regex' => 'The last name may only contain letters, spaces, hyphens and apostrophes.',
            'email.required' => 'Please enter an email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'An account with this email address already exists.',
            'gender.required' => 'Please select a gender.',
            'gender.in' => 'The selected gender is invalid.',
            'department.required' => 'Please select a department.',
            'department.exists' => 'The selected department is invalid.',
            'title.required' => 'Please select a title.',
            'title.in' => 'The selected title is invalid.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.letters' => 'The password must contain at least one letter.',
            'password.numbers' => 'The password must contain at least one number.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }
}
