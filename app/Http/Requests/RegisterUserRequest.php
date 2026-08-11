<?php

namespace App\Http\Requests;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

abstract class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ValidationRules::firstName(),
            'last_name' => ValidationRules::lastName(),
            'email' => ValidationRules::uniqueEmail(),
            'department' => ValidationRules::departmentId(),
            'gender' => ValidationRules::gender(),
            'password' => ValidationRules::requiredPassword(),
            'confirm_password' => ['required', 'same:password'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Please enter your first name.',
            'first_name.min' => 'The first name must be at least 2 characters.',
            'first_name.regex' => 'The first name may only contain letters, spaces, hyphens and apostrophes.',
            'last_name.required' => 'Please enter your last name.',
            'last_name.min' => 'The last name must be at least 2 characters.',
            'last_name.regex' => 'The last name may only contain letters, spaces, hyphens and apostrophes.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'An account with this email address already exists.',
            'department.required' => 'Please select your department.',
            'department.exists' => 'The selected department is invalid.',
            'gender.required' => 'Please select your gender.',
            'gender.in' => 'The selected gender is invalid.',
            'password.required' => 'Please enter a password.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.letters' => 'The password must contain at least one letter.',
            'password.numbers' => 'The password must contain at least one number.',
            'password.confirmed' => 'The password confirmation does not match.',
            'confirm_password.required' => 'Please confirm your password.',
            'confirm_password.same' => 'The password confirmation does not match.',
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
