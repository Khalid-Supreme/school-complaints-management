<?php

namespace App\Http\Requests\Audit;

use App\Enums\AuditAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAuditLogsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Route is restricted to role:admin.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'action' => ['sometimes', 'string', Rule::in(array_column(AuditAction::cases(), 'value'))],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'date_from' => ['sometimes', 'date', 'before_or_equal:date_to'],
            'date_to' => ['sometimes', 'date'],
            'ip_address' => ['sometimes', 'string', 'max:45'],
            'search' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }
}
