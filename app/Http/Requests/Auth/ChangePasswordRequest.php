<?php

namespace App\Http\Requests\Auth;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authenticated users may change their own password.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The current password is only required for voluntary changes. In the
     * forced flow (must_change_password) the user has already authenticated
     * with the temporary password this session, so it is not re-collected.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'nullable',
                'string',
                Rule::requiredIf(fn () => ! (bool) $this->user()?->must_change_password),
            ],
            'password' => ValidationRules::confirmedPassword(),
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Please enter your current password.',
            'password.required' => 'Please enter a new password.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password_confirmation.required' => 'Please confirm your new password.',
        ];
    }
}
