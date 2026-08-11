<?php

namespace App\Http\Requests\User;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class IndexStaffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Role-based access enforced by route middleware.
    }

    /**
     * Validate all query-string inputs used for filtering.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => ValidationRules::search(),
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'role' => ['nullable', 'string', 'in:staff,complaint_officer,sub_admin'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }
}
