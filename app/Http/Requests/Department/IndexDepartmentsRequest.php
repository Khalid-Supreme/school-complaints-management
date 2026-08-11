<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;

class IndexDepartmentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public read-only endpoint.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'in:academic,non-academic'],
        ];
    }
}
