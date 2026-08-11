<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * "username" is an email address or institution ID; max is aligned with the
     * login_attempts.email column width. encoding rejects invalid UTF-8.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'encoding:UTF-8', 'max:150'],
            'password' => ['required', 'string', 'encoding:UTF-8', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Please enter your email address or institution ID.',
            'username.encoding' => 'The username contains characters that are not valid UTF-8.',
            'username.max' => 'The username cannot be longer than 150 characters.',
        ];
    }
}
