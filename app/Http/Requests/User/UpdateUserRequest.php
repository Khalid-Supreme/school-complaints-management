<?php

namespace App\Http\Requests\User;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
        $ignoreId = $this->route('user')?->getKey();

        return [
            'first_name' => ValidationRules::optionalFirstName(),
            'last_name' => ValidationRules::optionalLastName(),
            'email' => ValidationRules::optionalEmail($ignoreId),
            'gender' => ValidationRules::optionalGender(),
            'title' => ValidationRules::optionalTitle(),
            'department' => ValidationRules::optionalDepartmentId(),
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.regex' => 'The first name may only contain letters, spaces, hyphens and apostrophes.',
            'last_name.regex' => 'The last name may only contain letters, spaces, hyphens and apostrophes.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'An account with this email address already exists.',
            'gender.in' => 'The selected gender is invalid.',
            'title.in' => 'The selected title is invalid.',
            'department.exists' => 'The selected department is invalid.',
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
