<?php

namespace App\Http\Requests\User;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class ResetUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization enforced in the controller (canManageUsers).
    }

    /**
     * An optional new password. When omitted (or too weak) the controller
     * falls back to a generated temporary password.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'password' => ValidationRules::optionalPassword(),
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'The password must be at least 8 characters.',
            'password.letters' => 'The password must contain at least one letter.',
            'password.numbers' => 'The password must contain at least one number.',
        ];
    }
}
