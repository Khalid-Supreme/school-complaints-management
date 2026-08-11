<?php

namespace App\Http\Requests;

use App\Support\ValidationRules;

class RegisterStaffRequest extends RegisterUserRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'title' => ValidationRules::title(),
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'title.required' => 'Please select your title.',
            'title.in' => 'The selected title is invalid.',
        ];
    }
}
