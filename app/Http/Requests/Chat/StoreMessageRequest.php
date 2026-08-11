<?php

namespace App\Http\Requests\Chat;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Gate in the controller.
    }

    public function rules(): array
    {
        return [
            'message' => ValidationRules::chatMessage(),
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Please enter a message.',
            'message.string' => 'The message must be text.',
            'message.max' => 'The message cannot be longer than 5000 characters.',
            'message.encoding' => 'The message contains characters that are not valid UTF-8.',
        ];
    }
}
