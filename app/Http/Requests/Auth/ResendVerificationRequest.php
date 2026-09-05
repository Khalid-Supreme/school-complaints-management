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
            'identifier' => ['sometimes', 'required_without:email', 'string', 'max:150'],
            'email' => ['sometimes', 'required_without:identifier', 'string', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required_without' => 'Please provide your Institution ID or email address.',
            'identifier.max' => 'The provided value is too long.',
            'email.required_without' => 'Please provide your Institution ID or email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'The email address cannot exceed 255 characters.',
        ];
    }
}
