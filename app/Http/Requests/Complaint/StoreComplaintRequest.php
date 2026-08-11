<?php

namespace App\Http\Requests\Complaint;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // We will use Policy in the controller
    }

    public function rules(): array
    {
        return [
            'category_id' => ValidationRules::categoryId(),
            'title' => ValidationRules::complaintTitle(),
            'description' => ValidationRules::complaintDescription(),
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'title.required' => 'Please enter a title for your complaint.',
            'title.min' => 'The title must be at least 3 characters.',
            'title.max' => 'The title cannot be longer than 255 characters.',
            'title.encoding' => 'The title contains characters that are not valid UTF-8.',
            'description.required' => 'Please describe your complaint.',
            'description.min' => 'The description must be at least 10 characters.',
            'description.max' => 'The description cannot be longer than 5000 characters.',
            'description.encoding' => 'The description contains characters that are not valid UTF-8.',
        ];
    }
}
