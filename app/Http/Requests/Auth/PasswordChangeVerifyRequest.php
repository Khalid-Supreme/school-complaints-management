<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class PasswordChangeVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // The authenticated user verifies their own pending change.
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'The verification code is required.',
            'code.regex' => 'The verification code must be 6 digits.',
        ];
    }
}
