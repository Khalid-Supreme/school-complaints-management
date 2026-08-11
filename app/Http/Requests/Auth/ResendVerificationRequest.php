<?php

namespace App\Http\Requests\Auth;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class ResendVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ValidationRules::email(),
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'The email address cannot exceed 255 characters.',
        ];
    }
}
